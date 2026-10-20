<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\NgWordAction;
use Tests\Support\DatabaseTestCase;

/**
 * NGワード判定（部分一致・完全一致・正規表現、拒否 > 要確認、無効なワードは無視）。CODING_RULES 13章
 *
 * @internal
 */
final class NgWordServiceTest extends DatabaseTestCase
{
    public function testNoMatchReturnsNull(): void
    {
        $result = service('ngWord')->check('こんなコンビニ店員は嫌だ');
        $this->assertNull($result['action']);
        $this->assertSame('', $result['detail']);
    }

    public function testPartialMatchIsCaseInsensitive(): void
    {
        // 「LINE」（部分一致・要確認）
        $this->assertSame(NgWordAction::Review, service('ngWord')->check('lineで送って')['action']);
    }

    public function testExactMatchOnlyMatchesWholeText(): void
    {
        // 「死ね」（完全一致・要確認）
        $this->assertSame(NgWordAction::Review, service('ngWord')->check(' 死ね ')['action']);
        $this->assertNull(service('ngWord')->check('死ねない体')['action']);
    }

    public function testRegexMatchesPhoneAndEmail(): void
    {
        $this->assertSame(NgWordAction::Reject, service('ngWord')->check('連絡は 090-1234-5678 まで')['action']);
        $this->assertSame(NgWordAction::Reject, service('ngWord')->check('mail: a.b+c@example.co.jp')['action']);
    }

    public function testRejectWinsOverReview(): void
    {
        $result = service('ngWord')->check('担任を殺す');
        $this->assertSame(NgWordAction::Reject, $result['action']);
        $this->assertStringContainsString('「殺す」', $result['detail']);
        $this->assertStringContainsString('「担任」', $result['detail']);
    }

    public function testDisabledWordIsIgnored(): void
    {
        // 「http」は無効
        $this->assertNull(service('ngWord')->check('https://example.com')['action']);
    }

    public function testCacheIsClearedOnChange(): void
    {
        $this->assertNull(service('ngWord')->check('ドローン')['action']);
        service('ngWord')->create(['word' => 'ドローン', 'match_type' => 'partial', 'category' => 'スパム', 'action' => 'reject', 'enabled' => '1']);
        $this->assertSame(NgWordAction::Reject, service('ngWord')->check('玉入れの玉が全部ドローン')['action']);
    }

    public function testCsvRoundTrip(): void
    {
        $csv = service('ngWord')->exportCsv();
        $this->assertStringContainsString('殺す', $csv);
        $count = service('ngWord')->importCsv($csv);
        $this->assertSame(8, $count);
        $this->assertSame(8, $this->db->table('ng_words')->countAll());
    }
}
