<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Answer;
use App\Entities\Oogiri;
use App\Enums\AnswerStatus;
use App\Enums\ErrorCode;
use App\Enums\EventName;
use App\Enums\OogiriStatus;
use App\Exceptions\AppException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use App\Models\AnswerModel;
use App\Models\OogiriModel;
use CodeIgniter\I18n\Time;

/**
 * 開催者の認証と操作（U-08・U-09）
 */
final class HostService
{
    public function __construct(
        private readonly OogiriModel $oogiris,
        private readonly AnswerModel $answers,
        private readonly IdentityService $identity,
        private readonly RateLimitService $rateLimit,
    ) {
    }

    /**
     * 開催者トークンを持つブラウザか（Cookie または X-Host-Token ヘッダー。同一ブラウザは原則自動認証）
     */
    public function isAuthorized(Oogiri $oogiri): bool
    {
        $token = $this->identity->hostTokenFromRequest($oogiri->slug);

        return $token !== '' && hash_equals($oogiri->host_token_hash, $this->identity->hash($token));
    }

    /**
     * 開催者が持っているトークン（Cookie）。U-03 の localStorage 保存用。なければ空
     */
    public function tokenFromRequest(Oogiri $oogiri): string
    {
        return $this->isAuthorized($oogiri) ? $this->identity->hostTokenFromRequest($oogiri->slug) : '';
    }

    /**
     * 合言葉で本人確認する（別端末からの復旧）。成功したらトークンを再発行してブラウザに保存する
     *
     * @throws AppException        試行回数の上限
     * @throws ValidationException 合言葉が違う
     */
    public function recover(Oogiri $oogiri, string $passphrase): string
    {
        if (trim($passphrase) === '') {
            throw new ValidationException(['passphrase' => '合言葉を入力してください']);
        }

        try {
            $this->rateLimit->assertAllowed(RateLimitService::ACTION_PASSPHRASE);
        } catch (AppException) {
            throw new AppException(ErrorCode::RateLimited, '試行回数の上限に達しました。しばらくしてからお試しください');
        }
        if ($oogiri->passphrase_hash === null || ! password_verify(trim($passphrase), $oogiri->passphrase_hash)) {
            throw new ValidationException(['passphrase' => '合言葉が違います']);
        }
        $token = $this->identity->issueHostToken();
        $this->oogiris->update($oogiri->id, ['host_token_hash' => $token['hash']]);
        $this->identity->rememberHostToken($oogiri->slug, $token['token']);
        service('adminLog')->record('oogiri', $oogiri->id, '合言葉で開催者ページを復旧', AdminLogService::ACTOR_HOST);

        return $token['token'];
    }

    /**
     * ブラウザが localStorage に持っていたトークンが正しければ Cookie に入れ直す（U-08）
     */
    public function restoreToken(Oogiri $oogiri, string $token): bool
    {
        if ($token === '' || ! hash_equals($oogiri->host_token_hash, $this->identity->hash($token))) {
            return false;
        }
        $this->identity->rememberHostToken($oogiri->slug, $token);

        return true;
    }

    public function toggleFavorite(Oogiri $oogiri, int $answerId): void
    {
        $this->assertAuthorized($oogiri);
        $answer = $this->answers->where('oogiri_id', $oogiri->id)->find($answerId);

        if ($answer !== null) {
            $this->answers->update($answerId, ['is_favorite' => $answer->is_favorite ? 0 : 1]);
        }
    }

    /**
     * 結果を確定する（U-09「この内容で結果を発表する」）
     *
     * @param list<int|string> $answerIds 1位から順。1〜3件
     *
     * @throws AppException        確定済み（ALREADY_DECIDED）
     * @throws ValidationException 選択なし
     */
    public function decide(Oogiri $oogiri, array $answerIds): void
    {
        $this->assertAuthorized($oogiri);

        if (in_array($oogiri->status(), [OogiriStatus::Decided, OogiriStatus::NoResult], true)) {
            throw new AppException(ErrorCode::AlreadyDecided);
        }
        if (! in_array($oogiri->status(), [OogiriStatus::AwaitingSelection, OogiriStatus::Open], true)) {
            throw new AppException(ErrorCode::Forbidden);
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $answerIds))));

        if ($ids === [] || count($ids) > 3) {
            throw new ValidationException(['ranks' => 'ベスト回答を1〜3件選んでください']);
        }
        $this->saveRanks($oogiri, $ids);
        $oogiri = $this->oogiris->find($oogiri->id);
        service('event')->record(EventName::Decide, ['oogiri_id' => $oogiri->id]);
        service('ogp')->generateResult($oogiri, $this->answers->find($ids[0]));
        service('adminLog')->record('oogiri', $oogiri->id, '結果を確定（' . count($ids) . '件）', AdminLogService::ACTOR_HOST);
    }

    /**
     * 順位を保存し、状態を decided にする（開催者の確定と管理者の代理変更で共通）
     *
     * @param list<int> $ids 1位から順
     */
    public function saveRanks(Oogiri $oogiri, array $ids): void
    {
        $db = $this->oogiris->db;
        $db->transStart();
        $this->answers->where('oogiri_id', $oogiri->id)->set(['rank' => null])->update();

        foreach ($ids as $i => $id) {
            $this->answers->where('oogiri_id', $oogiri->id)->where('status', AnswerStatus::Visible->value)->where('id', $id)->set(['rank' => $i + 1])->update();
        }
        $this->oogiris->update($oogiri->id, ['status' => OogiriStatus::Decided->value, 'decided_at' => Time::now()->toDateTimeString()]);
        $db->transComplete();
        service('pageCache')->purgeOogiri($oogiri->slug);
    }

    public function delete(Oogiri $oogiri): void
    {
        $this->assertAuthorized($oogiri);
        $this->oogiris->update($oogiri->id, ['status' => OogiriStatus::Deleted->value]);
        service('pageCache')->purgeOogiri($oogiri->slug);
        service('adminLog')->record('oogiri', $oogiri->id, '開催者が削除', AdminLogService::ACTOR_HOST);
    }

    /**
     * 開催者画面の回答（お気に入りを含む、新しい順）
     *
     * @return list<Answer>
     */
    public function answers(Oogiri $oogiri): array
    {
        return $this->answers->where('oogiri_id', $oogiri->id)->where('status', AnswerStatus::Visible->value)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll(200);
    }

    private function assertAuthorized(Oogiri $oogiri): void
    {
        if (! $this->isAuthorized($oogiri)) {
            throw new ForbiddenException('開催者だけが操作できます');
        }
    }
}
