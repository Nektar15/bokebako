<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Answer;
use App\Entities\Oogiri;
use App\Enums\ErrorCode;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\Fixtures\FixtureContext;
use App\Services\Fixtures\OogiriFixture;

/**
 * 回答の取得・登録・回答済み判定（U-04〜U-07、U-10、U-12）
 */
final class AnswerService
{
    public const PER_PAGE = 20;

    public function __construct(
        private readonly SettingService $settings,
        private readonly DisplayService $display,
    ) {
    }

    /**
     * 公開中の回答を新着順で返す
     *
     * @return array{items: list<Answer>, hasMore: bool, page: int}
     */
    public function listVisible(Oogiri $oogiri, int $page = 1): array
    {
        // TODO(inner): AnswerModel から status=visible を新着順・ページングで取得。is_mine はブラウザIDで判定
        $all   = OogiriFixture::answers(FixtureContext::name(), $oogiri->id);
        $items = array_slice($all, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        foreach ($items as $answer) {
            $answer->posted_label = $this->display->ago($answer->created_at); // View で日時計算をしないため、ここで文字列にする
        }

        return ['items' => $items, 'hasMore' => $oogiri->answer_count > $page * self::PER_PAGE, 'page' => $page];
    }

    /**
     * ベスト回答（rank 順）。未選出なら空
     */
    public function ranked(Oogiri $oogiri): array
    {
        $ranked = array_filter(OogiriFixture::answers(FixtureContext::name(), $oogiri->id), static fn (Answer $a) => $a->rank !== null);
        usort($ranked, static fn (Answer $a, Answer $b) => $a->rank <=> $b->rank);

        return array_values($ranked);
    }

    /**
     * @throws NotFoundException
     */
    public function find(Oogiri $oogiri, int $answerId): Answer
    {
        foreach (OogiriFixture::answers(FixtureContext::name(), $oogiri->id) as $answer) {
            if ($answer->id === $answerId) {
                return $answer;
            }
        }

        throw new NotFoundException();
    }

    /**
     * このブラウザがこの大喜利に回答済みか（ブラウザ保存情報＋サーバー側の投稿記録：U-07）
     */
    public function hasAnswered(Oogiri $oogiri): bool
    {
        // TODO(inner): ブラウザIDのハッシュで answers を検索する
        return FixtureContext::name() !== 'not_answered';
    }

    /**
     * 直前に投稿した回答（U-06）。なければ null
     */
    public function lastPosted(Oogiri $oogiri): ?Answer
    {
        // TODO(inner): セッション（フラッシュ）から取得する
        $answers = OogiriFixture::answers(FixtureContext::name(), $oogiri->id);

        return $answers[0] ?? null;
    }

    /**
     * 回答を登録する（U-05「同意して回答する」）
     *
     * @param array{body?: string} $input
     *
     * @throws AppException        締切後（CLOSED）、レート制限
     * @throws ValidationException 文字数・NGワード
     */
    public function create(Oogiri $oogiri, array $input): Answer
    {
        // 締切判定は画面の表示に依存せず、ここで必ず行う（S-01）
        if (! $oogiri->status()->acceptsAnswers()) {
            throw new AppException(ErrorCode::Closed);
        }

        $errors = $this->validate($input);

        // TODO(inner): NGワード判定、連続投稿の判定（S-03。閾値は運用設定）、投稿元識別情報の保存
        match (FixtureContext::error()) {
            'ngword'    => $errors['body'] = ErrorCode::NgWord->message(),
            'ratelimit' => throw new AppException(ErrorCode::RateLimited),
            default     => null,
        };

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        // TODO(inner): 保存、回答数カウンタの加算、「回答」イベントの記録、回答済みの記録
        return OogiriFixture::answers('open', $oogiri->id)[0];
    }

    /**
     * @param array{body?: string} $input
     *
     * @return array<string, string>
     */
    private function validate(array $input): array
    {
        $body = trim((string) ($input['body'] ?? ''));
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
