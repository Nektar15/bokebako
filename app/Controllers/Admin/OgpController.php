<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Entities\OgpJob;
use App\Enums\OgpKind;
use App\Exceptions\ValidationException;
use App\Libraries\OgpImage;
use App\Services\OgpService;
use App\Services\SettingService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

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
            'bulk'     => $this->bulkLabel(),
            'previews' => $this->previewUrls(),
            'texts'    => $this->textForm(),
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
     * A-08 画像に載せる文言と順位の保存（ADR 0012）
     */
    public function updateTexts(): RedirectResponse
    {
        try {
            service('settings')->updateOgp($this->request->getPost());
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        }

        return $this->backWithNotice('画像の文言を保存しました。作成済みの画像に反映するには「作り直しを予約する」を押してください');
    }

    /**
     * A-08 プレビュー画像（見本のお題・回答で、今の文言・背景・ロゴを使って描く）
     */
    public function preview(string $kind): ResponseInterface
    {
        $png = service('ogp')->preview(OgpKind::tryFrom($kind) ?? throw PageNotFoundException::forPageNotFound());

        if ($png === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response->setHeader('Content-Type', 'image/png')->setHeader('Cache-Control', 'no-store')->setBody($png);
    }

    /**
     * 「画像に載せる文言」フォームの値（入力エラーで戻ったときは入力した値）
     *
     * @return array{values: array<string, string>, fields: array<string, array{string, int, bool}>, ranks: array<int, string>}
     */
    private function textForm(): array
    {
        $all    = service('settings')->all();
        $values = [];

        foreach ([...array_keys(SettingService::OGP_TEXTS), 'ogp_result_ranks'] as $key) {
            $old          = old($key);
            $values[$key] = is_string($old) ? $old : (string) $all[$key];
        }

        return ['values' => $values, 'fields' => SettingService::OGP_TEXTS, 'ranks' => SettingService::OGP_RESULT_RANKS];
    }

    /**
     * 種類ごとのプレビュー画像の URL。作れない環境では見本画像
     *
     * @return array<string, string>
     */
    private function previewUrls(): array
    {
        $canDraw = (new OgpImage())->isAvailable();
        $version = substr(md5((string) json_encode(service('settings')->ogpTexts()) . (string) @filemtime(WRITEPATH . 'ogp-templates')), 0, 8);
        $urls    = [];

        foreach (OgpKind::cases() as $kind) {
            $urls[$kind->value] = $canDraw ? admin_url('ogp/preview/' . $kind->value) . '?v=' . $version : OgpService::sampleUrl($kind);
        }

        return $urls;
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
