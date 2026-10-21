<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entities\Answer;
use Tests\Support\DatabaseTestCase;

/**
 * 結果シェア（U-10）に1〜3位を載せる（ADR 0011）
 *
 * @internal
 */
final class ResultShareTest extends DatabaseTestCase
{
    private function decodedText(string $intent): string
    {
        parse_str((string) parse_url($intent, PHP_URL_QUERY), $query);

        return (string) $query['text'];
    }

    public function testResultShareListsTopThree(): void
    {
        $oogiri = service('oogiri')->findBySlug('q8Lr4nTb');
        $text   = $this->decodedText(service('share')->resultIntent($oogiri, service('answer')->ranked($oogiri)));

        $this->assertStringContainsString("1位：うまい棒・味なし\n2位：チロルチョコ 1,980円\n3位：よっちゃんイカ・本人", $text);
        $this->assertStringContainsString(site_url('o/q8Lr4nTb'), $text);
    }

    public function testLongAnswersAreShortenedToFitX(): void
    {
        $oogiri = service('oogiri')->findBySlug('q8Lr4nTb');
        $ranked = [];

        foreach ([1, 2, 3] as $rank) {
            $ranked[] = new Answer(['id' => $rank, 'rank' => $rank, 'body' => str_repeat('長い回答' . $rank, 35)]);
        }
        $text = $this->decodedText(service('share')->resultIntent($oogiri, $ranked));

        // X の数え方（日本語は2、URL は23）で 280 以内
        $url    = site_url('o/q8Lr4nTb');
        $weight = 23;

        foreach (mb_str_split(str_replace($url, '', $text)) as $char) {
            $weight += strlen($char) === 1 ? 1 : 2;
        }
        $this->assertLessThanOrEqual(280, $weight);
        $this->assertSame(3, substr_count($text, '…'));
        $this->assertStringContainsString('3位：長い回答3', $text);
    }

    public function testFirstOnlyTemplateStillWorks(): void
    {
        $oogiri = service('oogiri')->findBySlug('q8Lr4nTb');
        // 1位だけのテンプレート（運用設定で変えた場合）
        db_connect()->table('settings')->where('name', 'tpl_result')->delete();
        db_connect()->table('settings')->insert(['name' => 'tpl_result', 'value' => '1位：{first} {url}']);
        service('settings')->clearCache();
        $text = $this->decodedText(service('share')->resultIntent($oogiri, service('answer')->ranked($oogiri)));

        $this->assertStringStartsWith('1位：うまい棒・味なし ', $text);
        $this->assertStringNotContainsString('2位', $text);
    }
}
