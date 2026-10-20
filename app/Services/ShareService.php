<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Answer;
use App\Entities\Oogiri;

/**
 * X の Web Intent URL と投稿文の組み立て（U-03 / U-06 / U-10）
 * 文面テンプレートとハッシュタグは運用設定（A-10）から読む。
 */
final class ShareService
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    /** 募集URL（Xでシェアする公開URL） */
    public function url(Oogiri $oogiri): string
    {
        return site_url('o/' . $oogiri->slug);
    }

    public function answerUrl(Oogiri $oogiri, Answer $answer): string
    {
        return site_url('o/' . $oogiri->slug . '/a/' . $answer->id);
    }

    public function recruitIntent(Oogiri $oogiri): string
    {
        return $this->intent($this->settings->string('tpl_recruit'), ['{odai}' => $oogiri->odai, '{url}' => $this->url($oogiri)]);
    }

    public function answerIntent(Oogiri $oogiri, Answer $answer): string
    {
        return $this->intent($this->settings->string('tpl_answer'), ['{odai}' => $oogiri->odai, '{answer}' => $answer->body, '{url}' => $this->answerUrl($oogiri, $answer)]);
    }

    public function resultIntent(Oogiri $oogiri, ?Answer $first): string
    {
        return $this->intent($this->settings->string('tpl_result'), ['{odai}' => $oogiri->odai, '{first}' => $first?->body ?? '（選出なし）', '{url}' => $this->url($oogiri)]);
    }

    /** @param array<string, string> $vars */
    private function intent(string $template, array $vars): string
    {
        $text = strtr($template, $vars) . "\n#" . $this->settings->string('hashtag');

        return 'https://x.com/intent/post?text=' . rawurlencode($text);
    }
}
