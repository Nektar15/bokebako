<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 広告枠（A-07）。掲載期間内の広告を枠ごとに返す。
 *
 * TODO(inner): ads テーブルから取得し、複数あればローテーション、表示回数を記録する。
 *
 * @phpstan-type Ad array{id:int, image:string, alt:string}
 */
final class AdService
{
    /**
     * @return array{top: Ad|null, bottom: Ad|null}
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
}
