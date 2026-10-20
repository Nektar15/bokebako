<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\ModerationItem;
use App\Enums\ModerationFlag;
use App\Exceptions\NotFoundException;
use App\Services\Fixtures\ModerationFixture;

/**
 * 通報・モデレーション（A-05）：待ち行列の取得と、管理者による処理
 */
final class ModerationService
{
    /**
     * 絞り込みの選択肢（値 => 表示名）。'all' は未処理すべて、'resolved' は処理済み
     */
    public const FILTERS = [
        'all'       => '未処理すべて',
        'surge'     => '通報急増',
        'ng_word'   => 'NGワード検知',
        'ai_review' => 'AI「要確認」以上',
        'abnormal'  => '異常投稿',
        'resolved'  => '処理済み',
    ];

    /**
     * 投稿元を制限する期間の選択肢（値 => 表示名）
     */
    public const RESTRICT_HOURS = [
        '1'   => '1時間',
        '24'  => '24時間',
        '168' => '7日間',
    ];

    /**
     * 待ち行列（優先度の高い順：通報急増 → 通報件数 → NG検知 → 異常投稿 → AI判定）
     *
     * @return list<ModerationItem>
     */
    public function queue(string $filter): array
    {
        // TODO(inner): reports・NG検知・異常投稿・AI判定を対象ごとにまとめ、優先度で並べる（LIMIT 必須）
        $items = ModerationFixture::queue();

        return array_values(array_filter($items, static fn (ModerationItem $item) => match ($filter) {
            'resolved'  => $item->resolved,
            'ai_review' => in_array($item->aiVerdict()->value, ['review', 'hide'], true),
            'all'       => ! $item->resolved,
            default     => in_array(ModerationFlag::from($filter), $item->flags(), true),
        }));
    }

    /**
     * 絞り込みごとの件数
     *
     * @return array<string, int> FILTERS のキー => 件数
     */
    public function counts(): array
    {
        // TODO(inner): それぞれ COUNT する
        return ModerationFixture::counts();
    }

    /**
     * 待ち行列の1件（キーは ModerationItem::key()）
     *
     * @throws NotFoundException
     */
    public function find(string $key): ModerationItem
    {
        foreach (ModerationFixture::queue() as $item) {
            if ($item->key() === $key) {
                return $item;
            }
        }

        throw new NotFoundException();
    }

    /**
     * 問題なし（通報を処理済みにする）
     */
    public function resolve(string $key): void
    {
        $this->find($key);
        // TODO(inner): reports を処理済みに、非表示中なら再公開はしない（別操作）、対応履歴の記録
    }

    /**
     * 非表示にする
     */
    public function hide(string $key): void
    {
        $this->find($key);
        // TODO(inner): 対象（お題／回答）を hidden に、キャッシュ破棄、対応履歴の記録
    }

    /**
     * 削除する
     */
    public function delete(string $key): void
    {
        $this->find($key);
        // TODO(inner): 対象を deleted に、キャッシュ破棄、対応履歴の記録
    }

    /**
     * 投稿元を一定期間制限する
     *
     * @param int $hours RESTRICT_HOURS のキー
     */
    public function restrict(string $key, int $hours): void
    {
        $this->find($key);
        // TODO(inner): 投稿元（IP・ブラウザIDのハッシュ）を hours 時間の投稿停止に、対応履歴の記録
    }
}
