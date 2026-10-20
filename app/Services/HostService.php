<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Oogiri;
use App\Enums\ErrorCode;
use App\Enums\OogiriStatus;
use App\Exceptions\AppException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use App\Services\Fixtures\FixtureContext;

/**
 * 開催者の認証と操作（U-08・U-09）
 */
final class HostService
{
    /**
     * 開催者トークンを持つブラウザか（同一ブラウザは原則自動認証）
     */
    public function isAuthorized(Oogiri $oogiri): bool
    {
        // TODO(inner): Cookie/localStorage のトークンをハッシュ化して oogiris.host_token_hash と照合する
        return FixtureContext::name() !== 'recover';
    }

    /**
     * 合言葉で本人確認する（別端末からの復旧）
     *
     * @throws AppException        試行回数の上限
     * @throws ValidationException 合言葉が違う
     */
    public function recover(Oogiri $oogiri, string $passphrase): void
    {
        // TODO(inner): password_verify、試行回数の制限、成功したらトークンを再発行してブラウザに保存
        if (trim($passphrase) === '') {
            throw new ValidationException(['passphrase' => '合言葉を入力してください']);
        }
        if (FixtureContext::error() === 'passphrase') {
            throw new ValidationException(['passphrase' => '合言葉が違います']);
        }
        if (FixtureContext::error() === 'ratelimit') {
            throw new AppException(ErrorCode::RateLimited, '試行回数の上限に達しました。しばらくしてからお試しください');
        }
    }

    public function toggleFavorite(Oogiri $oogiri, int $answerId): void
    {
        $this->assertAuthorized($oogiri);
        // TODO(inner): answers.is_favorite を反転
    }

    /**
     * 結果を確定する（U-09「この内容で結果を発表する」）
     *
     * @param list<int> $answerIds 1位から順。1〜3件
     *
     * @throws AppException 確定済み（ALREADY_DECIDED）、選択なし
     */
    public function decide(Oogiri $oogiri, array $answerIds): void
    {
        $this->assertAuthorized($oogiri);

        if ($oogiri->status() === OogiriStatus::Decided) {
            throw new AppException(ErrorCode::AlreadyDecided);
        }
        $answerIds = array_values(array_unique(array_filter(array_map('intval', $answerIds))));
        if ($answerIds === [] || count($answerIds) > 3) {
            throw new ValidationException(['ranks' => 'ベスト回答を1〜3件選んでください']);
        }
        // TODO(inner): 順位の保存、状態を decided に、結果用OGPの生成、結果ページキャッシュの生成、「結果確定」イベント
    }

    public function delete(Oogiri $oogiri): void
    {
        $this->assertAuthorized($oogiri);
        // TODO(inner): 状態を deleted に、キャッシュ破棄
    }

    private function assertAuthorized(Oogiri $oogiri): void
    {
        if (! $this->isAuthorized($oogiri)) {
            throw new ForbiddenException('開催者だけが操作できます');
        }
    }
}
