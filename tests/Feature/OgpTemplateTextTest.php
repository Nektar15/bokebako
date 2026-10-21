<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OgpKind;
use App\Libraries\OgpImage;
use App\Models\AdminModel;
use App\Services\OgpService;
use Tests\Support\DatabaseTestCase;

/**
 * A-08 OGP 画像の文言と載せる順位の編集（ADR 0012）
 *
 * @internal
 */
final class OgpTemplateTextTest extends DatabaseTestCase
{
    private const VALID = [
        'ogp_recruit_pill'  => '回答募集中！',
        'ogp_answer_prefix' => '',
        'ogp_result_stamp'  => '発表',
        'ogp_result_prefix' => 'お題 ',
        'ogp_result_count'  => '{count}件の回答',
        'ogp_result_ranks'  => '1',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->asAdmin();
    }

    public function testPageShowsFieldsAndPreviews(): void
    {
        $body = (string) $this->get('admin/ogp')->getBody();

        foreach (array_keys(self::VALID) as $key) {
            $this->assertStringContainsString('name="' . $key . '"', $body);
        }
        $this->assertStringContainsString('<form id="ogp-texts"', $body);
        // テスト環境には OGP 用フォントがないので、見本画像を出す
        $expected = (new OgpImage())->isAvailable() ? admin_url('ogp/preview/result') : OgpService::sampleUrl(OgpKind::Result);
        $this->assertStringContainsString($expected, $body);
    }

    public function testSaveTexts(): void
    {
        $this->post('admin/ogp/texts', self::VALID)->assertRedirect();

        $texts = service('settings')->ogpTexts();
        $this->assertSame('回答募集中！', $texts['recruit_pill']);
        $this->assertSame('', $texts['answer_prefix']);
        $this->assertSame('{count}件の回答', $texts['result_count']);
        $this->assertSame(1, $texts['result_ranks']);
        $this->seeInDatabase('admin_logs', ['target_type' => 'setting']);
    }

    public function testInvalidTextsAreRejected(): void
    {
        $this->post('admin/ogp/texts', ['ogp_recruit_pill' => '', 'ogp_result_stamp' => str_repeat('長', 7), 'ogp_result_ranks' => '5'] + self::VALID)->assertRedirect();
        $this->assertSame('24時間限定 回答募集中', service('settings')->ogpTexts()['recruit_pill']);
        $this->assertSame(3, service('settings')->ogpTexts()['result_ranks']);
    }

    /**
     * A-10 の保存では、A-08 の文言を消さない
     */
    public function testSettingsPageSaveKeepsOgpTexts(): void
    {
        $this->post('admin/ogp/texts', self::VALID)->assertRedirect();
        $input = [];

        foreach (service('settings')->all() as $key => $value) {
            $input[$key] = is_bool($value) ? ($value ? '1' : '0') : (is_array($value) ? $value : (string) $value);
        }
        service('currentAdmin')->set(model(AdminModel::class)->where('login_id', 'dev-admin')->first()->toAdminUser());
        service('settings')->update($input);
        $this->assertSame('回答募集中！', service('settings')->ogpTexts()['recruit_pill']);
    }

    /**
     * 文言と順位を変えて描ける（フォントがある環境だけ）
     */
    public function testDrawWithCustomTexts(): void
    {
        $font  = '/usr/share/fonts/opentype/noto/NotoSansCJK-Bold.ttc';
        $image = new OgpImage($font);

        if (! $image->isAvailable()) {
            $this->markTestSkipped('OGP 用のフォントがない');
        }

        foreach ([
            $image->recruit('お題', ['recruit_pill' => '募集中']),
            $image->answer('お題', '回答', ['answer_prefix' => '']),
            $image->result('お題', [['rank' => 1, 'body' => '一位']], 3, ['result_count' => '']),
            $image->result('お題', [['rank' => 1, 'body' => '一位'], ['rank' => 2, 'body' => '二位'], ['rank' => 3, 'body' => '三位']], 3),
        ] as $img) {
            $this->assertSame([OgpImage::WIDTH, OgpImage::HEIGHT], [imagesx($img), imagesy($img)]);
        }
    }
}
