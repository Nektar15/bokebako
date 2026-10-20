<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\OgpJob;
use App\Enums\OgpKind;
use App\Exceptions\ValidationException;
use App\Services\Fixtures\OgpFixture;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * OGP 画像の管理（A-08）：プレビュー・再生成・テンプレート画像の差し替え
 *
 * 画像の生成そのものは工程4で app/Libraries/ に置く（S-07）。
 */
final class OgpService
{
    /**
     * テンプレート画像の種類（値 => 表示名）
     */
    public const ASSETS = [
        'background' => '背景',
        'logo'       => 'ロゴ',
    ];

    /**
     * 最近の生成（新しい順）
     *
     * @return list<OgpJob>
     */
    public function recentJobs(): array
    {
        // TODO(inner): ogp_jobs から新しい順に取る（LIMIT 必須）
        return OgpFixture::recentJobs();
    }

    /**
     * 個別に再生成する
     *
     * @param string $target 大喜利のURL・スラッグ・ID、または回答ID（例：A-90412）
     *
     * @throws ValidationException
     */
    public function regenerate(OgpKind $kind, string $target): void
    {
        $target = trim($target);

        if ($target === '') {
            throw new ValidationException(['target' => $kind === OgpKind::Answer ? '回答IDを入力してください' : '大喜利のURL・スラッグ・IDを入力してください']);
        }
        // TODO(inner): 対象を特定して画像を生成し直し、ogp_jobs に記録する
    }

    /**
     * テンプレートの背景・ロゴを差し替える
     *
     * @param string $asset ASSETS のキー
     *
     * @throws ValidationException
     */
    public function replaceAsset(OgpKind $kind, string $asset, ?UploadedFile $file): void
    {
        if (! isset(self::ASSETS[$asset])) {
            throw new ValidationException(['asset' => '差し替える画像の種類を選んでください']);
        }
        if ($file === null || ! $file->isValid()) {
            throw new ValidationException(['file' => '画像ファイルを選んでください']);
        }
        // TODO(inner): PNG/JPG の確認、writable/ogp-templates/ へ保存、操作履歴の記録
    }
}
