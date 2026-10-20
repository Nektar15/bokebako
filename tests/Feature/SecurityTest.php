<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\DatabaseTestCase;

/**
 * セキュリティ（SPEC 3.4）：ヘッダー、JSON API の受け付け条件（ADR 0005）、公開ディレクトリの外
 *
 * @internal
 */
final class SecurityTest extends DatabaseTestCase
{
    public function testSecurityHeadersAreSent(): void
    {
        $result = $this->get('/');
        $result->assertOK();
        $result->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $result->assertHeader('X-Content-Type-Options', 'nosniff');
        $result->assertHeader('Referrer-Policy', 'same-origin');
        $this->assertStringContainsString("default-src 'self'", $result->response()->getHeaderLine('Content-Security-Policy'));
        $this->assertStringContainsString('challenges.cloudflare.com', $result->response()->getHeaderLine('Content-Security-Policy'));
        // HSTS は HTTPS を強制する production でだけ
        $this->assertSame('', $result->response()->getHeaderLine('Strict-Transport-Security'));
    }

    public function testApiPostWithoutJsonOrAjaxHeaderIsRejected(): void
    {
        $result = $this->post('/api/v1/reports', ['slug' => 'k7Qm2xPz', 'type' => 'answer', 'id' => '90411', 'reason' => 'abuse']);
        $result->assertStatus(400);
        $result->assertJSONFragment(['ok' => false]);
        $this->dontSeeInDatabase('reports', ['target_id' => 90411]);
    }

    public function testApiPostWithAjaxHeaderIsAccepted(): void
    {
        $result = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->post('/api/v1/reports', ['slug' => 'k7Qm2xPz', 'type' => 'answer', 'id' => '90411', 'reason' => 'abuse']);
        $result->assertOK();
        $this->seeInDatabase('reports', ['target_id' => 90411]);
    }

    public function testApiGetNeedsNoHeader(): void
    {
        $this->get('/api/v1/o/k7Qm2xPz/status')->assertOK();
    }

    public function testPublicDirectoryDoesNotContainSecrets(): void
    {
        $this->assertFileDoesNotExist(FCPATH . '.env');
        $this->assertDirectoryDoesNotExist(FCPATH . 'writable');
        $this->assertStringContainsString('RewriteRule ^ https://', (string) file_get_contents(FCPATH . '.htaccess'));
    }
}
