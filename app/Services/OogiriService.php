<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Oogiri;
use App\Enums\ErrorCode;
use App\Enums\EventName;
use App\Enums\NgWordAction;
use App\Enums\OogiriStatus;
use App\Enums\ReportTarget;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Libraries\Captcha;
use App\Models\OogiriModel;
use CodeIgniter\I18n\Time;

/**
 * 大喜利の取得・作成（U-01〜U-03、募集URLの振り分け）
 */
final class OogiriService
{
    /**
     * 合言葉の最小文字数（未確定事項2：任意。入れるなら4文字以上を仮置き）
     */
    public const PASSPHRASE_MIN = 4;

    public function __construct(
        private readonly OogiriModel $oogiris,
        private readonly SettingService $settings,
        private readonly IdentityService $identity,
        private readonly NgWordService $ngWords,
        private readonly RateLimitService $rateLimit,
        private readonly ModerationService $moderation,
    ) {
    }

    /**
     * 募集URLのスラッグから大喜利を取得する。削除済みも返す（U-13 を出すため）
     *
     * @throws NotFoundException
     */
    public function findBySlug(string $slug): Oogiri
    {
        $oogiri = $this->oogiris->where('slug', $slug)->first();

        if ($oogiri === null) {
            throw new NotFoundException();
        }

        return $oogiri;
    }

    /**
     * 大喜利を作成する（U-02「同意して開催する」）
     *
     * @param array{odai?: string, passphrase?: string, from?: string, captcha?: string} $input
     *
     * @return array{oogiri: Oogiri, hostToken: string} トークンはこの場でだけ渡す（DB にはハッシュだけ）
     *
     * @throws AppException        レート制限
     * @throws ValidationException 文字数・NGワード
     */
    public function create(array $input): array
    {
        $this->rateLimit->assertAllowed(RateLimitService::ACTION_CREATE);
        $errors = $this->validate($input);
        $odai   = trim((string) ($input['odai'] ?? ''));

        if ($errors === []) {
            $ng = $this->ngWords->check($odai);

            if ($ng['action'] === NgWordAction::Reject) {
                $errors['odai'] = ErrorCode::NgWord->message();
            }
        }
        if ($errors === [] && $this->rateLimit->needsCaptcha(RateLimitService::ACTION_CREATE) && ! (new Captcha())->verify((string) ($input['captcha'] ?? ''))) {
            $errors['_'] = 'CAPTCHA の確認に失敗しました。もう一度お試しください';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $now        = Time::now();
        $token      = $this->identity->issueHostToken();
        $passphrase = trim((string) ($input['passphrase'] ?? ''));
        $parent     = $this->parentIdFromSlug((string) ($input['from'] ?? ''));
        // 開催と派生元の派生開催数の加算は一緒に成功・失敗させる
        $id     = $this->oogiris->transaction(fn (): int => $this->insertOogiri($odai, $token['hash'], $passphrase, $parent, $now));
        $oogiri = $this->oogiris->find($id);
        $this->identity->rememberHostToken($oogiri->slug, $token['token']);
        service('event')->record(EventName::Create, ['oogiri_id' => $id, 'parent_oogiri_id' => $parent]);
        service('ogp')->generateRecruit($oogiri);
        service('adminLog')->record('oogiri', $id, '開催', AdminLogService::ACTOR_HOST);

        // NGワード「要確認」は開催を通したうえで管理画面に記録する（FEATURES 0-3）
        if (($ng['action'] ?? null) === NgWordAction::Review) {
            $this->moderation->flag(ReportTarget::Oogiri, $id, 'ng_review', $ng['detail']);
            service('mail')->notifyAdmin('ngReview', ['id' => $id, 'detail' => $ng['detail'], 'url' => admin_url('moderation?item=oogiri-' . $id)]);
        }
        if ($this->rateLimit->isBursting(RateLimitService::ACTION_CREATE)) {
            $this->moderation->flag(ReportTarget::Oogiri, $id, 'abnormal', null, '同一環境から短時間に連続して開催');
        }

        return ['oogiri' => $oogiri, 'hostToken' => $token['token']];
    }

    /**
     * 大喜利の行を入れ、派生元があれば派生開催数を足す（transaction() の中で呼ぶ）
     */
    private function insertOogiri(string $odai, string $tokenHash, string $passphrase, ?int $parent, Time $now): int
    {
        $id = (int) $this->oogiris->insert([
            'slug'             => $this->uniqueSlug(),
            'odai'             => $odai,
            'host_token_hash'  => $tokenHash,
            'passphrase_hash'  => $passphrase === '' ? null : password_hash($passphrase, PASSWORD_DEFAULT),
            'browser_id_hash'  => $this->identity->browserIdHash(),
            'ip_hash'          => $this->identity->ipHash(),
            'status'           => OogiriStatus::Open->value,
            'starts_at'        => $now->toDateTimeString(),
            'ends_at'          => $now->addHours($this->settings->int('open_hours'))->toDateTimeString(),
            'parent_oogiri_id' => $parent,
        ]);

        if ($parent !== null) {
            $this->oogiris->increment($parent, 'derived_count');
        }

        return $id;
    }

    /**
     * 派生元のスラッグから ID を引く（U-01 の ?from=）。なければ null
     */
    private function parentIdFromSlug(string $slug): ?int
    {
        if ($slug === '') {
            return null;
        }
        $parent = $this->oogiris->select('id')->where('slug', $slug)->first();

        return $parent?->id;
    }

    private function uniqueSlug(): string
    {
        do {
            $slug = $this->identity->newSlug();
        } while ($this->oogiris->where('slug', $slug)->countAllResults() > 0);

        return $slug;
    }

    /**
     * @param array{odai?: string, passphrase?: string} $input
     *
     * @return array<string, string>
     */
    private function validate(array $input): array
    {
        $odai       = trim((string) ($input['odai'] ?? ''));
        $passphrase = trim((string) ($input['passphrase'] ?? ''));
        $max        = $this->settings->int('odai_max');
        $errors     = [];

        if ($odai === '') {
            $errors['odai'] = 'お題を入力してください';
        } elseif (mb_strlen($odai) > $max) {
            $errors['odai'] = "お題は{$max}文字以内で入力してください";
        } elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $odai) === 1) {
            $errors['odai'] = '使えない文字が含まれています';
        }
        if ($passphrase !== '' && mb_strlen($passphrase) < self::PASSPHRASE_MIN) {
            $errors['passphrase'] = '合言葉は' . self::PASSPHRASE_MIN . '文字以上にしてください';
        }

        return $errors;
    }
}
