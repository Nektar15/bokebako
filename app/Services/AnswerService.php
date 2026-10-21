<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Answer;
use App\Entities\Oogiri;
use App\Enums\AnswerStatus;
use App\Enums\ErrorCode;
use App\Enums\EventName;
use App\Enums\NgWordAction;
use App\Enums\ReportTarget;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Libraries\Captcha;
use App\Libraries\Text;
use App\Models\AnswerModel;
use App\Models\OogiriModel;
use CodeIgniter\I18n\Time;

/**
 * 回答の取得・登録・回答済み判定（U-04〜U-07、U-10、U-12）
 */
final class AnswerService
{
    public const PER_PAGE = 20;

    /**
     * 直前に投稿した回答の ID を置くセッションのキー（U-06）
     */
    public const SESSION_LAST = 'bk_last_answer';

    public function __construct(
        private readonly AnswerModel $answers,
        private readonly OogiriModel $oogiris,
        private readonly SettingService $settings,
        private readonly DisplayService $display,
        private readonly IdentityService $identity,
        private readonly NgWordService $ngWords,
        private readonly RateLimitService $rateLimit,
        private readonly ModerationService $moderation,
    ) {
    }

    /**
     * 公開中の回答を新着順で返す（ページング）
     *
     * is_mine（「あなたの回答」）は $markMine のときだけブラウザIDで判定する。
     * 全員で共有するページキャッシュ（U-10・U-12）に載せると、最初に開いた人の判定が他の人にも出るため（レビュー指摘 2）
     *
     * @return array{items: list<Answer>, hasMore: bool, page: int}
     */
    public function listVisible(Oogiri $oogiri, int $page, bool $markMine): array
    {
        $items = $this->answers->where('oogiri_id', $oogiri->id)->where('status', AnswerStatus::Visible->value)
            ->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')
            ->findAll(self::PER_PAGE + 1, ($page - 1) * self::PER_PAGE);
        $hasMore = count($items) > self::PER_PAGE;
        $items   = array_slice($items, 0, self::PER_PAGE);
        $mine    = $markMine ? $this->identity->browserIdHash() : '';

        foreach ($items as $answer) {
            $answer->is_mine      = $mine !== '' && $answer->browser_id_hash === $mine;
            $answer->posted_label = $this->display->ago($answer->created_at); // View で日時計算をしないため、ここで文字列にする
        }

        return ['items' => $items, 'hasMore' => $hasMore, 'page' => $page];
    }

    /**
     * ベスト回答（rank 順）。未選出なら空
     *
     * @return list<Answer>
     */
    public function ranked(Oogiri $oogiri): array
    {
        return $this->answers->where('oogiri_id', $oogiri->id)->where('rank IS NOT NULL')->where('status', AnswerStatus::Visible->value)->orderBy('rank', 'ASC')->findAll(3);
    }

    /**
     * 公開中の回答を1件（回答シェア用URL）
     *
     * @throws NotFoundException
     */
    public function find(Oogiri $oogiri, int $answerId): Answer
    {
        $answer = $this->answers->where('oogiri_id', $oogiri->id)->where('status', AnswerStatus::Visible->value)->find($answerId);

        if ($answer === null) {
            throw new NotFoundException();
        }

        return $answer;
    }

    /**
     * このブラウザがこの大喜利に回答済みか（サーバー側の投稿記録で判定：U-07）
     */
    public function hasAnswered(Oogiri $oogiri): bool
    {
        $mine = $this->identity->browserIdHash();

        return $mine !== '' && $this->answers->where('oogiri_id', $oogiri->id)->where('browser_id_hash', $mine)->countAllResults() > 0;
    }

    /**
     * 直前に投稿した回答（U-06。セッションから）。なければ null
     */
    public function lastPosted(Oogiri $oogiri): ?Answer
    {
        $id = (int) session()->get(self::SESSION_LAST . ':' . $oogiri->slug);

        return $id > 0 ? $this->answers->where('oogiri_id', $oogiri->id)->find($id) : null;
    }

    /**
     * 回答を登録する（U-05「同意して回答する」）
     *
     * @param array{body?: string, captcha?: string} $input
     *
     * @throws AppException        締切後（CLOSED）、レート制限
     * @throws ValidationException 文字数・NGワード
     */
    public function create(Oogiri $oogiri, array $input): Answer
    {
        // 締切判定は画面の表示に依存せず、ここで必ず行う（S-01）
        if (! $oogiri->status()->acceptsAnswers() || Time::now()->isAfter($oogiri->ends_at)) {
            throw new AppException(ErrorCode::Closed);
        }
        $this->rateLimit->assertAllowed(RateLimitService::ACTION_ANSWER);
        $errors = $this->validate($input);
        $body   = Text::clean((string) ($input['body'] ?? ''));
        $ng     = ['action' => null, 'detail' => ''];

        if ($errors === []) {
            $ng = $this->ngWords->check($body);

            if ($ng['action'] === NgWordAction::Reject) {
                $errors['body'] = ErrorCode::NgWord->message();
            }
        }
        if ($errors === [] && $this->rateLimit->needsCaptcha(RateLimitService::ACTION_ANSWER) && ! (new Captcha())->verify((string) ($input['captcha'] ?? ''))) {
            $errors['_'] = 'CAPTCHA の確認に失敗しました。もう一度お試しください';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        // 回答の保存と回答数の加算は一緒に成功・失敗させる（片方だけ残ると回答数がずれる）
        $id = $this->answers->transaction(function () use ($oogiri, $body): int {
            $id = (int) $this->answers->insert([
                'oogiri_id'       => $oogiri->id,
                'body'            => $body,
                'status'          => AnswerStatus::Visible->value,
                'browser_id_hash' => $this->identity->browserIdHash(),
                'ip_hash'         => $this->identity->ipHash(),
            ]);
            $this->oogiris->increment($oogiri->id, 'answer_count');

            return $id;
        });
        session()->set(self::SESSION_LAST . ':' . $oogiri->slug, $id);
        service('event')->record(EventName::Answer, ['oogiri_id' => $oogiri->id, 'answer_id' => $id]);

        if ($ng['action'] === NgWordAction::Review) {
            $this->moderation->flag(ReportTarget::Answer, $id, 'ng_review', $ng['detail']);
        }
        if ($this->rateLimit->isBursting(RateLimitService::ACTION_ANSWER)) {
            $this->moderation->flag(ReportTarget::Answer, $id, 'abnormal', null, '同一環境から' . $this->settings->int('burst_window_sec') . '秒間に' . $this->settings->int('burst_count') . '件を超える投稿');
        }

        return $this->answers->find($id);
    }

    /**
     * @param array{body?: string} $input
     *
     * @return array<string, string>
     */
    private function validate(array $input): array
    {
        $body = Text::clean((string) ($input['body'] ?? ''));
        $max  = $this->settings->int('answer_max');

        if ($body === '') {
            return ['body' => 'ボケを入力してください'];
        }
        if (mb_strlen($body) > $max) {
            return ['body' => "回答は{$max}文字以内で入力してください"];
        }
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $body) === 1) {
            return ['body' => '使えない文字が含まれています'];
        }

        return [];
    }
}
