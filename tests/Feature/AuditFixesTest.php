<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Entities\AdminUser;
use App\Enums\AdminRole;
use App\Enums\OgpKind;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Libraries\Text;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use Tests\Support\DatabaseTestCase;

/**
 * 仕様の突き合わせ・バグ調査（2026-10-21）で直したもの（ADR 0013）
 *
 * @internal
 */
final class AuditFixesTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    private function asSystemAdmin(): void
    {
        service('currentAdmin')->set(new AdminUser(1, 'test-admin', AdminRole::Super));
    }

    /**
     * @param list<array{string, string}> $sources [ブラウザ, IP] の組
     */
    private function report(string $type, int $id, array $sources): void
    {
        $now = Time::now()->toDateTimeString();

        foreach ($sources as [$browser, $ip]) {
            db_connect()->table('reports')->insert([
                'target_type' => $type, 'target_id' => $id, 'reason' => 'abuse', 'browser_id_hash' => hash('sha256', $browser),
                'ip_hash'     => hash('sha256', $ip), 'resolved' => 0, 'created_at' => $now,
            ]);
        }
    }

    public function testSlugIsCaseSensitive(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('/o/Q8LR4NTB');
    }

    public function testReportsFromOneNetworkDoNotHide(): void
    {
        // Cookie だけ変えて同じ IP から3件 → 非表示にしない
        $this->report('answer', 90411, [['c1', '198.51.100.1'], ['c2', '198.51.100.1'], ['c3', '198.51.100.1']]);
        $this->assertSame(0, service('moderation')->processReports());
        $this->seeInDatabase('answers', ['id' => 90411, 'status' => 'visible']);

        // 別の IP からなら非表示
        $this->report('answer', 90411, [['c4', '198.51.100.2'], ['c5', '198.51.100.3']]);
        $this->assertSame(1, service('moderation')->processReports());
        $this->seeInDatabase('answers', ['id' => 90411, 'status' => 'hidden']);
    }

    public function testRepublishResolvesReportsAndStaysPublic(): void
    {
        $this->report('answer', 90411, [['c1', '198.51.100.1'], ['c2', '198.51.100.2'], ['c3', '198.51.100.3']]);
        service('moderation')->processReports();
        $this->asSystemAdmin();
        service('answerAdmin')->publish(90411);
        service('moderation')->processReports();
        $this->seeInDatabase('answers', ['id' => 90411, 'status' => 'visible']);
    }

    public function testDecidedOogiriIsAutoHiddenByReports(): void
    {
        $this->report('oogiri', 12452, [['c1', '198.51.100.1'], ['c2', '198.51.100.2'], ['c3', '198.51.100.3']]);
        $this->assertSame(1, service('moderation')->processReports());
        $this->seeInDatabase('oogiris', ['id' => 12452, 'status' => 'hidden']);
    }

    public function testSurgeIsNotifiedOncePerHour(): void
    {
        $sources = [];

        for ($i = 0; $i < 11; $i++) {
            $sources[] = ['s' . $i, '192.0.2.1'];
        }
        $this->report('answer', 90412, $sources);
        service('moderation')->processReports();
        service('moderation')->processReports();
        $this->assertSame(1, db_connect()->table('admin_logs')->like('what', '通報の急増')->countAllResults());
    }

    public function testCaptchaCheckDoesNotCount(): void
    {
        db_connect()->table('settings')->insert(['name' => 'limit_method', 'value' => 'captcha']);
        service('settings')->clearCache();

        for ($i = 0; $i < 10; $i++) {
            $this->assertFalse(service('rateLimit')->needsCaptcha('answer'));
        }
    }

    public function testDailyStatsTwiceDoesNotDoublePv(): void
    {
        $day = Time::now()->subDays(1);
        service('stats')->aggregateDay($day);
        $pv = (int) db_connect()->table('oogiris')->where('id', 12480)->get()->getRow()->pv;
        service('stats')->aggregateDay($day);
        $this->assertSame($pv, (int) db_connect()->table('oogiris')->where('id', 12480)->get()->getRow()->pv);
    }

    public function testFailedLoginDoesNotKeepPassword(): void
    {
        // 本番では $_POST に入る（テストの post() は入れないので、同じ形にしておく）
        $_POST = ['login_id' => 'dev-admin', 'password' => 'wrong-secret-123'];
        $this->post('admin/login', $_POST)->assertRedirect();
        $old   = $_SESSION['_ci_old_input']['post'] ?? [];
        $_POST = [];
        $this->assertArrayNotHasKey('password', $old);
        $this->assertSame('dev-admin', $old['login_id'] ?? null);
    }

    public function testArrayInputIsNot500(): void
    {
        $this->post('/o/k7Qm2xPz/answers', ['body' => ['x']])->assertRedirect();
        $this->post('oogiri', ['odai' => ['x']])->assertRedirect();
        $this->withBodyFormat('json')->post('api/v1/events', ['name' => ['x']])->assertStatus(400);
    }

    public function testBlankAnswersAreRejected(): void
    {
        $this->assertSame('', Text::clean("　 \u{200B}\u{FEFF}　"));
        $this->post('/o/k7Qm2xPz/answers', ['body' => "　　\u{200B}"])->assertRedirect();
        $this->dontSeeInDatabase('answers', ['oogiri_id' => 12480, 'body' => "　　\u{200B}"]);
    }

    public function testNgWordCannotBeBypassedWithInvisibleOrWidthChars(): void
    {
        db_connect()->table('ng_words')->insert(['word' => 'spamword', 'match_type' => 'partial', 'category' => '誹謗中傷', 'action' => 'reject', 'enabled' => 1, 'created_at' => Time::now()->toDateTimeString(), 'updated_at' => Time::now()->toDateTimeString()]);
        db_connect()->table('ng_words')->insert(['word' => 'ばかやろう', 'match_type' => 'partial', 'category' => '誹謗中傷', 'action' => 'reject', 'enabled' => 1, 'created_at' => Time::now()->toDateTimeString(), 'updated_at' => Time::now()->toDateTimeString()]);
        cache()->delete('ng_words');
        $this->assertNotNull(service('ngWord')->check("この、ばか\u{200B}やろう")['action']);
        $this->assertNotNull(service('ngWord')->check('ＳＰＡＭＷＯＲＤです')['action']); // 全角英字もそろえて照合
    }

    public function testResultShowsRealRankWhenFirstIsHidden(): void
    {
        $oogiri = service('oogiri')->findBySlug('q8Lr4nTb');
        $first  = service('answer')->ranked($oogiri)[0];
        $this->asSystemAdmin();
        service('answerAdmin')->hide($first->id);

        $body = (string) $this->get('/o/q8Lr4nTb')->getBody();
        $this->assertMatchesRegularExpression('/p-result__rank--first">2(位|&#20301;)/u', $body);
    }

    public function testHostCannotDecideWhileOpen(): void
    {
        $this->expectException(AppException::class);
        service('host')->decide(service('oogiri')->findBySlug('k7Qm2xPz'), [90412]);
    }

    public function testHostPageOfHiddenOogiriRedirects(): void
    {
        $this->asSystemAdmin();
        service('oogiriAdmin')->hide(12480);
        $this->asHost()->get('/host/k7Qm2xPz')->assertRedirectTo(site_url('o/k7Qm2xPz'));
    }

    public function testAnswerShareAfterCloseUsesAnswerImage(): void
    {
        $oogiri = service('oogiri')->findBySlug('q8Lr4nTb');
        $answer = service('answer')->ranked($oogiri)[1];
        $result = $this->get('/o/q8Lr4nTb/a/' . $answer->id);
        $result->assertOK();
        $result->assertSee('シェアされたボケ');
        $this->assertStringContainsString(service('ogp')->url(OgpKind::Answer, (string) $answer->id), (string) $result->getBody());
    }

    public function testHostEntryLinkOnAnswerPage(): void
    {
        $this->assertStringContainsString(site_url('host/k7Qm2xPz'), (string) $this->get('/o/k7Qm2xPz')->getBody());
    }

    public function testOgpRegenerateRefusesHiddenTargets(): void
    {
        $this->asSystemAdmin();
        service('oogiriAdmin')->hide(12452);
        $this->expectException(ValidationException::class);
        service('ogp')->regenerate(OgpKind::Result, 'q8Lr4nTb');
    }

    public function testContactRejectsJavascriptUrl(): void
    {
        $this->expectException(ValidationException::class);
        service('contact')->send(['type' => 'delete', 'email' => 'a@example.com', 'url' => 'javascript://x/%0aalert(1)', 'body' => 'テスト']);
    }

    public function testCsvImportIsAllOrNothing(): void
    {
        $this->asSystemAdmin();
        $before = db_connect()->table('ng_words')->countAllResults();

        try {
            service('ngWord')->importCsv("word,match_type,category,action,enabled\nあたらしいNG,partial,誹謗中傷,reject,1\nだめな行,unknown,誹謗中傷,reject,1\n");
            $this->fail('検証エラーにならない');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('2行目', implode('', $e->errors));
        }
        $this->assertSame($before, db_connect()->table('ng_words')->countAllResults());
    }

    public function testCustomPeriodFiltersAdminList(): void
    {
        $from = Time::now()->subDays(1)->toDateString();
        $body = (string) $this->asAdmin()->get('admin/oogiri?from=' . $from . '&to=' . Time::now()->toDateString())->getBody();
        $this->assertStringContainsString('#12480', $body);       // 5時間前に開催
        $this->assertStringNotContainsString('#12301', $body);    // 何日も前
        $this->assertStringContainsString('value="' . $from . '"', $body);
    }

    public function testDashboardCustomPeriod(): void
    {
        $body = (string) $this->asAdmin()->get('admin?period=custom&from=2026-10-01&to=2026-10-07')->getBody();
        $this->assertStringContainsString('name="from"', $body);
    }

    public function testRankCandidatesIncludeOldAnswers(): void
    {
        $rows = [];

        for ($i = 0; $i < 30; $i++) {
            $rows[] = ['oogiri_id' => 12452, 'body' => 'NEW-' . $i, 'status' => 'visible', 'browser_id_hash' => 'x', 'ip_hash' => 'y', 'created_at' => Time::now()->toDateTimeString(), 'updated_at' => Time::now()->toDateTimeString()];
        }
        db_connect()->table('answers')->insertBatch($rows);
        $oogiri     = service('oogiriAdmin')->find(12452);
        $candidates = array_map(static fn ($a) => $a->id, service('oogiriAdmin')->rankCandidates($oogiri));

        foreach (service('oogiriAdmin')->ranked($oogiri) as $answer) {
            $this->assertContains($answer->id, $candidates);
        }
    }

    public function testResultPageLoadsAdsSeparately(): void
    {
        $this->assertStringContainsString('data-js-ad-slot="top"', (string) $this->get('/o/q8Lr4nTb')->getBody());
        $result = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get('api/v1/ads');
        $result->assertOK();
        $result->assertJSONFragment(['ok' => true]);
    }
}
