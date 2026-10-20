<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Ad;
use App\Enums\AdSlot;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\Fixtures\AdFixture;

/**
 * 広告（A-07）：ユーザー向けの枠への表示と、管理画面の登録・編集
 *
 * @phpstan-type SlotAd  array{id:int, image:string, alt:string}
 * @phpstan-type AdInput array{name?: string, sponsor?: string, url?: string, slot?: string, starts_at?: string, ends_at?: string, action?: string}
 */
final class AdService
{
    /**
     * ユーザー向けの枠に出す広告
     *
     * TODO(inner): ads テーブルから掲載期間内のものを取得し、複数あればローテーション、表示回数を記録する。
     *
     * @return array{top: SlotAd|null, bottom: SlotAd|null}
     */
    public function slots(): array
    {
        // 外側フェーズ：?ads=1 のときだけ見本の広告を出す
        if (ENVIRONMENT === 'development' && service('request')->getGet('ads') === '1') {
            $ad = ['id' => 1, 'image' => base_url('assets/img/ad-sample.svg'), 'alt' => '見本の広告'];

            return ['top' => $ad, 'bottom' => $ad];
        }

        return ['top' => null, 'bottom' => null];
    }

    /**
     * 管理画面の一覧（掲載中 → 予約 → 下書き → 停止 → 終了の順）
     *
     * @return list<Ad>
     */
    public function list(): array
    {
        // TODO(inner): AdModel から取る（LIMIT 必須）
        return AdFixture::all();
    }

    /**
     * 枠ごとの今月の実績
     *
     * @return array<string, array{live: int, impressions: int, clicks: int}> AdSlot の値 => 実績
     */
    public function slotSummary(): array
    {
        // TODO(inner): 掲載中の本数と、今月の表示回数・クリック数を枠ごとに合算する
        return AdFixture::slotSummary();
    }

    /**
     * @throws NotFoundException
     */
    public function find(int $id): Ad
    {
        foreach (AdFixture::all() as $ad) {
            if ($ad->id === $id) {
                return $ad;
            }
        }

        throw new NotFoundException();
    }

    /**
     * 登録する（action が 'publish' なら公開、それ以外は下書き）
     *
     * @param AdInput $input
     *
     * @throws ValidationException
     */
    public function create(array $input): void
    {
        $this->validate($input);
        // TODO(inner): 画像の保存（public/assets/ads/）、ads への保存、操作履歴の記録
    }

    /**
     * 編集する（公開・停止もここ）
     *
     * @param AdInput $input
     *
     * @throws ValidationException
     */
    public function update(int $id, array $input): void
    {
        $this->find($id);
        $this->validate($input);
        // TODO(inner): 画像の差し替え、ads の更新、操作履歴の記録
    }

    public function delete(int $id): void
    {
        $this->find($id);
        // TODO(inner): ads から削除（表示回数の記録は残す）、操作履歴の記録
    }

    /**
     * @param AdInput $input
     *
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        $errors = [];
        $url    = trim((string) ($input['url'] ?? ''));

        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = '広告名を入力してください';
        }
        if (trim((string) ($input['sponsor'] ?? '')) === '') {
            $errors['sponsor'] = 'スポンサー名を入力してください';
        }
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false || ! str_starts_with($url, 'https://')) {
            $errors['url'] = 'リンク先URLを https:// から入力してください';
        }
        if (AdSlot::tryFrom((string) ($input['slot'] ?? '')) === null) {
            $errors['slot'] = '掲載場所を選んでください';
        }
        if (($input['starts_at'] ?? '') === '' || ($input['ends_at'] ?? '') === '') {
            $errors['period'] = '掲載期間を入力してください';
        } elseif ($input['starts_at'] >= $input['ends_at']) {
            $errors['period'] = '掲載終了は掲載開始より後にしてください';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
