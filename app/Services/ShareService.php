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
    /**
     * X の上限（少し余裕をもたせる）
     */
    private const X_LIMIT = 270;

    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * 募集URL（Xでシェアする公開URL）
     */
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

    /**
     * 回答シェア。自分の回答は tpl_answer（「俺の回答」）、ほかの人の回答は tpl_answer_other（BUG-008）
     *
     * 共有キャッシュで配る結果ページ（U-10・U-12）は本人かどうかを付けないので、ほかの人の回答として扱う
     */
    public function answerIntent(Oogiri $oogiri, Answer $answer, bool $mine = false): string
    {
        return $this->intent($this->settings->string($mine ? 'tpl_answer' : 'tpl_answer_other'), ['{odai}' => $oogiri->odai, '{answer}' => $answer->body, '{url}' => $this->answerUrl($oogiri, $answer)]);
    }

    /**
     * 結果シェア（U-10）。{ranks} は1〜3位を1行ずつ（「1位：〜」）。{first} は1位だけ（ADR 0011）
     *
     * X の文字数の上限（280。日本語は1文字を2と数え、URL は23）に収まるよう、長い回答から順に末尾を「…」にする
     *
     * @param list<Answer> $ranked 順位の若い順
     */
    public function resultIntent(Oogiri $oogiri, array $ranked): string
    {
        $template = $this->settings->string('tpl_result');
        $url      = $this->url($oogiri);
        $vars     = ['{odai}' => $oogiri->odai, '{first}' => $ranked === [] ? '（選出なし）' : self::oneLine($ranked[0]->body), '{url}' => $url];

        if (str_contains($template, '{ranks}')) {
            $base            = strtr($template, $vars + ['{ranks}' => '']) . "\n#" . $this->settings->string('hashtag');
            $vars['{ranks}'] = $this->ranksText($ranked, self::X_LIMIT - self::weight($base, $url));
        }

        return $this->intent($template, $vars);
    }

    /**
     * 「1位：〜」を1行ずつ。$budget（X の数え方）に収まるまで、いちばん長い回答の末尾を削る
     *
     * @param list<Answer> $ranked
     */
    private function ranksText(array $ranked, int $budget): string
    {
        if ($ranked === []) {
            return '（選出なし）';
        }
        $bodies = array_map(static fn (Answer $a): array => mb_str_split(self::oneLine($a->body)), $ranked);
        /** @var list<bool> $cut 末尾を削った回答 */
        $cut   = array_fill(0, count($bodies), false);
        $build = static function () use (&$bodies, &$cut, $ranked): string {
            $lines = [];

            foreach ($ranked as $i => $answer) {
                $lines[] = $answer->rank . '位：' . implode('', $bodies[$i]) . ($cut[$i] ? '…' : '');
            }

            return implode("\n", $lines);
        };

        while (self::weight($build(), '') > $budget) {
            $longest = 0;

            foreach ($bodies as $i => $chars) {
                if (count($chars) > count($bodies[$longest])) {
                    $longest = $i;
                }
            }
            if (count($bodies[$longest]) <= 1) {
                break;
            }
            array_pop($bodies[$longest]);
            $cut[$longest] = true;
        }

        return $build();
    }

    private static function oneLine(string $text): string
    {
        return trim((string) preg_replace('/\s*\R\s*/u', ' ', $text));
    }

    /**
     * X の数え方での長さ（半角は1、それ以外は2、URL は23）
     */
    private static function weight(string $text, string $url): int
    {
        $urls = 0;

        if ($url !== '') {
            $urls = substr_count($text, $url);
            $text = str_replace($url, '', $text);
        }
        $weight = $urls * 23;

        foreach (mb_str_split($text) as $char) {
            $weight += strlen($char) === 1 ? 1 : 2;
        }

        return $weight;
    }

    /**
     * @param array<string, string> $vars
     */
    private function intent(string $template, array $vars): string
    {
        $text = strtr($template, $vars) . "\n#" . $this->settings->string('hashtag');

        return 'https://x.com/intent/post?text=' . rawurlencode($text);
    }
}
