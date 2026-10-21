<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Filters\BrowserId;
use App\Models\AnswerModel;
use Config\ScreenNav;

/**
 * 画面一覧ナビ（確認用。ADR 0009）：全画面へのプルダウンと、そのための「開催者として」「回答したブラウザとして」開く準備
 *
 * 開発用データ（DevSeeder）の開催者トークン・ブラウザIDを使う。screenNav.enabled が false のときは何もしない（ルートもない）。
 */
final class ScreenNavService
{
    /**
     * DevSeeder の回答 A-90412 を投稿したブラウザ（tests/_support/DatabaseTestCase::BROWSER_ID と同じ）
     */
    public const DEV_BROWSER_ID = 'dev-browser-me';

    public function __construct(
        private readonly ScreenNav $config,
        private readonly AnswerModel $answers,
        private readonly IdentityService $identity,
    ) {
    }

    public function enabled(): bool
    {
        return $this->config->enabled;
    }

    /**
     * プルダウンの中身（表示用）。番号は go() に渡す値
     *
     * @return list<array{label: string, options: list<array{index: int, label: string, note: string}>}>
     */
    public function groups(): array
    {
        $groups = [];
        $index  = 0;

        foreach ($this->config->items as $label => $items) {
            $options = [];

            foreach ($items as [$name, , $note]) {
                $options[] = ['index' => $index++, 'label' => $name, 'note' => $note];
            }
            $groups[] = ['label' => $label, 'options' => $options];
        }

        return $groups;
    }

    /**
     * 番号の画面へ行く準備をして、行き先の URL を返す（開催者・回答者として開く画面は Cookie とセッションを用意する）
     *
     * @throws NotFoundException 無効、または番号がない
     */
    public function go(int $index): string
    {
        $target        = $this->target($index);
        [$kind, $rest] = explode(':', $target, 2);

        return match ($kind) {
            'url'      => site_url(ltrim($rest, '/')),
            'admin'    => admin_url(ltrim($rest, '/')),
            'host'     => $this->asHost(...explode(':', $rest, 2)),
            'answerer' => $this->asAnswerer(...explode(':', $rest, 2)),
            default    => throw new NotFoundException(),
        };
    }

    /**
     * @throws NotFoundException
     */
    private function target(int $index): string
    {
        if (! $this->enabled()) {
            throw new NotFoundException();
        }
        $all = array_merge(...array_values($this->config->items));

        if (! isset($all[$index])) {
            throw new NotFoundException();
        }

        return $all[$index][1];
    }

    /**
     * 開催者としてログインした状態にする（DevSeeder の開催者トークン dev-token-{slug}）
     */
    private function asHost(string $slug, string $path): string
    {
        service('host')->restoreToken(service('oogiri')->findBySlug($slug), 'dev-token-' . $slug);

        return site_url(ltrim($path, '/'));
    }

    /**
     * 回答したブラウザにする（匿名ブラウザIDの Cookie と、U-06 用の「直前に投稿した回答」）
     */
    private function asAnswerer(string $slug, string $path): string
    {
        service('response')->setCookie(BrowserId::COOKIE_NAME, self::DEV_BROWSER_ID, BrowserId::LIFETIME, '', '/', '', null, true, 'Lax');
        $oogiri = service('oogiri')->findBySlug($slug);
        $answer = $this->answers->where('oogiri_id', $oogiri->id)->where('browser_id_hash', $this->identity->hash(self::DEV_BROWSER_ID))->first();

        if ($answer !== null) {
            session()->set(AnswerService::SESSION_LAST . ':' . $slug, $answer->id);
        }

        return site_url(ltrim($path, '/'));
    }
}
