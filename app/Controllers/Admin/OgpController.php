<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Entities\OgpJob;
use App\Enums\OgpKind;
use App\Exceptions\ValidationException;
use App\Services\OgpService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * A-08 OGP管理（プレビュー・個別の再生成・テンプレート画像の差し替え）
 */
final class OgpController extends AdminController
{
    /**
     * A-08
     */
    public function index(): string
    {
        $display = service('display');

        return $this->render('admin/a08_ogp', [
            'screenId' => 'A-08',
            'title'    => 'OGP管理',
            'kinds'    => OgpKind::cases(),
            'assets'   => OgpService::ASSETS,
            'form'     => [
                'kind'   => (string) old('kind', OgpKind::Recruit->value),
                'target' => (string) old('target', ''),
            ],
            'jobs' => array_map(static fn (OgpJob $job) => [
                'job'    => $job,
                'time'   => $display->short($job->created_at),
                'status' => $job->succeeded ? 'ok' : 'failed',
                'label'  => $job->succeeded ? '完了' : '失敗',
            ], service('ogp')->recentJobs()),
            'bulk' => $this->bulkLabel(),
        ]);
    }

    /**
     * A-08 個別に再生成（一覧の「再試行」も同じ）
     */
    public function regenerate(): RedirectResponse
    {
        $kind = OgpKind::tryFrom((string) $this->request->getPost('kind')) ?? OgpKind::Recruit;

        try {
            service('ogp')->regenerate($kind, (string) $this->request->getPost('target'));
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        }

        return $this->backWithNotice($kind->label() . 'のOGP画像を再生成しました');
    }

    /**
     * A-08 公開中の画像をすべて今のテンプレートで作り直す（予約。cron の ogp:generate が少しずつ進める）
     */
    public function regenerateAll(): RedirectResponse
    {
        service('ogp')->requestRegenerateAll();

        return $this->backWithNotice('作り直しを予約しました。10分ごとに少しずつ進みます');
    }

    /**
     * 一括の作り直しの進み具合（表示用）。予約がなければ null
     *
     * @return array{requestedAt: string, remaining: string}|null
     */
    private function bulkLabel(): ?array
    {
        $bulk = service('ogp')->bulkStatus();

        return $bulk === null ? null : ['requestedAt' => service('display')->short($bulk['requestedAt']), 'remaining' => service('display')->number($bulk['remaining'])];
    }

    /**
     * A-08 テンプレートの背景・ロゴの差し替え
     */
    public function updateTemplate(): RedirectResponse
    {
        $kind = OgpKind::tryFrom((string) $this->request->getPost('kind')) ?? OgpKind::Recruit;

        try {
            service('ogp')->replaceAsset($kind, (string) $this->request->getPost('asset'), $this->request->getFile('file'));
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        }

        return $this->backWithNotice($kind->label() . 'の画像を差し替えました');
    }
}
