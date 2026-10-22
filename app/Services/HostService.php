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
use App\Libraries\Passphrase;
use App\Models\AnswerModel;
use App\Models\OogiriModel;
use CodeIgniter\I18n\Time;

/**
 * 開催者の認証と操作（U-08・U-09）
 */
final class HostService
{
    /**
     * 開催者が結果を確定できる状態（SPEC U-08：募集終了後。募集中は画面にもボタンを出さない。監査 2026-10-21）
     */
    public const DECIDABLE_BY_HOST = [OogiriStatus::AwaitingSelection];

    /**
     * 開催者が削除できる状態（募集中・結果選択待ち）
     */
    public const DELETABLE_BY_HOST = [OogiriStatus::Open, OogiriStatus::AwaitingSelection];

    /**
     * 開催者画面で一度に出す回答の件数
     */
    public const PER_PAGE = 100;

    /**
     * 結果選択待ちで上にまとめて出すいいねの上限
     */
    public const FAVORITES_MAX = 100;

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
        if (Passphrase::normalize($passphrase) === '') {
            throw new ValidationException(['passphrase' => '合言葉を入力してください']);
        }

        try {
            $this->rateLimit->assertAllowed(RateLimitService::ACTION_PASSPHRASE, $oogiri->slug);
        } catch (AppException) {
            throw new AppException(ErrorCode::RateLimited, '試行回数の上限に達しました。しばらくしてからお試しください');
        }
        if ($oogiri->passphrase_hash === null || ! Passphrase::verify($passphrase, $oogiri->passphrase_hash)) {
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
     * @throws ValidationException 選択なし、選べない回答を含む
     */
    public function decide(Oogiri $oogiri, array $answerIds): void
    {
        $this->assertAuthorized($oogiri);

        if (in_array($oogiri->status(), [OogiriStatus::Decided, OogiriStatus::NoResult], true)) {
            throw new AppException(ErrorCode::AlreadyDecided);
        }
        if (! in_array($oogiri->status(), self::DECIDABLE_BY_HOST, true)) {
            throw new AppException(ErrorCode::Forbidden);
        }
        $ids = self::normalizeIds($answerIds);
        $this->saveRanks($oogiri, $ids, self::DECIDABLE_BY_HOST);
        $oogiri = $this->oogiris->find($oogiri->id);
        service('event')->record(EventName::Decide, ['oogiri_id' => $oogiri->id]);
        service('ogp')->generateResult($oogiri);
        service('adminLog')->record('oogiri', $oogiri->id, '結果を確定（' . count($ids) . '件）', AdminLogService::ACTOR_HOST);
    }

    /**
     * 選ばれた回答IDを整える（数値化・重複と空欄（0）を除く）。1〜3件でなければ拒否する
     *
     * @param list<int|string> $answerIds
     *
     * @return list<int>
     *
     * @throws ValidationException
     */
    public static function normalizeIds(array $answerIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $answerIds))));

        if ($ids === [] || count($ids) > 3) {
            throw new ValidationException(['ranks' => 'ベスト回答を1〜3件選んでください']);
        }

        return $ids;
    }

    /**
     * 順位を保存し、状態を decided にする（開催者の確定と管理者の代理変更で共通）
     *
     * - 選んだ回答がすべて「この大喜利の公開中の回答」でなければ、何も保存せずに拒否する
     * - 状態が $from のどれかであることを更新の条件にする。先に読んだ Entity の状態は古いことがあり、
     *   同時に確定したときに後の処理が先の結果を上書きしないため（レビュー指摘 4）
     *
     * @param list<int>          $ids  1位から順（normalizeIds() を通したもの）
     * @param list<OogiriStatus> $from 確定してよい今の状態
     *
     * @return Answer 1位の回答（OGP の結果画像に使う）
     *
     * @throws AppException        状態が変わっていた（ALREADY_DECIDED）
     * @throws ValidationException 選べない回答を含む
     */
    public function saveRanks(Oogiri $oogiri, array $ids, array $from): Answer
    {
        $this->oogiris->transaction(function () use ($oogiri, $ids, $from): void {
            $this->markDecided($oogiri, $from);
            $this->answers->where('oogiri_id', $oogiri->id)->set(['rank' => null])->update();

            foreach ($ids as $i => $id) {
                $this->answers->where('oogiri_id', $oogiri->id)->where('status', AnswerStatus::Visible->value)->where('id', $id)->set(['rank' => $i + 1])->update();

                if ($this->answers->affectedRows() !== 1) {
                    throw new ValidationException(['ranks' => '選べない回答が含まれています（非表示・削除された可能性があります）。選び直してください']);
                }
            }
        });
        service('pageCache')->purgeOogiri($oogiri->slug);

        return $this->answers->find($ids[0]);
    }

    /**
     * 状態が $from のどれかのときだけ decided にする（条件付き更新）
     *
     * @param list<OogiriStatus> $from
     *
     * @throws AppException 状態が変わっていた
     */
    private function markDecided(Oogiri $oogiri, array $from): void
    {
        $values = array_map(static fn (OogiriStatus $s) => $s->value, $from);
        $this->oogiris->where('id', $oogiri->id)->whereIn('status', $values)
            ->set(['status' => OogiriStatus::Decided->value, 'decided_at' => Time::now()->toDateTimeString()])->update();

        if ($this->oogiris->affectedRows() === 1) {
            return;
        }
        // MariaDB は値が変わらない更新を 0 件と数える（管理者が同じ秒に再変更した場合など）。今の状態で判定し直す
        $current = $this->oogiris->find($oogiri->id);

        if ($current === null || ! in_array($current->status(), $from, true)) {
            throw new AppException(ErrorCode::AlreadyDecided);
        }
    }

    /**
     * 開催者が削除する（募集中・結果選択待ちだけ）。状態は読んだときのままであることを条件にする（確定と同時に消さない）
     *
     * @throws AppException       削除できる状態でない、ほかの操作と重なった
     * @throws ForbiddenException 開催者でない
     */
    public function delete(Oogiri $oogiri): void
    {
        $this->assertAuthorized($oogiri);

        if (! in_array($oogiri->status(), self::DELETABLE_BY_HOST, true)) {
            throw new AppException(ErrorCode::Forbidden, 'この大喜利は削除できません');
        }
        $this->oogiris->where('id', $oogiri->id)->where('status', $oogiri->status)
            ->set(['status' => OogiriStatus::Deleted->value, 'status_before_hide' => $oogiri->status])->update();

        if ($this->oogiris->affectedRows() !== 1) {
            throw new AppException(ErrorCode::Conflict);
        }
        service('pageCache')->purgeOogiri($oogiri->slug);
        service('ogp')->removeOogiri($oogiri);
        service('adminLog')->record('oogiri', $oogiri->id, '開催者が削除', AdminLogService::ACTOR_HOST);
    }

    /**
     * 開催者画面の回答（新しい順、PER_PAGE 件ずつ）
     *
     * 回答が多い大喜利でも古い回答まで選べるよう、ページで区切って続きを読めるようにする（レビュー「取得上限」）。
     * 結果選択待ちではいいねを favorites() で上にまとめて出すので、ここからは外す
     *
     * @return array{items: list<Answer>, hasMore: bool, page: int}
     */
    public function answers(Oogiri $oogiri, int $page = 1, bool $excludeFavorites = false): array
    {
        $builder = $this->answers->where('oogiri_id', $oogiri->id)->where('status', AnswerStatus::Visible->value);

        if ($excludeFavorites) {
            $builder->where('is_favorite', 0);
        }
        $items   = $builder->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll(self::PER_PAGE + 1, ($page - 1) * self::PER_PAGE);
        $hasMore = count($items) > self::PER_PAGE;

        return ['items' => $this->withLabels(array_slice($items, 0, self::PER_PAGE)), 'hasMore' => $hasMore, 'page' => $page];
    }

    /**
     * いいねにした回答（新しい順、FAVORITES_MAX 件まで）。結果選択待ちで上にまとめて出す
     *
     * @return list<Answer>
     */
    public function favorites(Oogiri $oogiri): array
    {
        return $this->withLabels($this->answers->where('oogiri_id', $oogiri->id)->where('status', AnswerStatus::Visible->value)->where('is_favorite', 1)
            ->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll(self::FAVORITES_MAX));
    }

    public function favoriteCount(Oogiri $oogiri): int
    {
        return $this->answers->where('oogiri_id', $oogiri->id)->where('status', AnswerStatus::Visible->value)->where('is_favorite', 1)->countAllResults();
    }

    /**
     * 投稿からの経過（「3分前」）を付ける。View で日時を計算しないため
     *
     * @param list<Answer> $answers
     *
     * @return list<Answer>
     */
    private function withLabels(array $answers): array
    {
        foreach ($answers as $answer) {
            $answer->posted_label = service('display')->ago($answer->created_at);
        }

        return $answers;
    }

    private function assertAuthorized(Oogiri $oogiri): void
    {
        if (! $this->isAuthorized($oogiri)) {
            throw new ForbiddenException('開催者だけが操作できます');
        }
    }
}
