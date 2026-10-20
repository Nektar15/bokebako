<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Database\Seeds\DevSeeder;
use App\Enums\ErrorCode;
use App\Enums\OogiriStatus;
use App\Exceptions\AppException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use App\Filters\BrowserId;
use App\Services\RateLimitService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use Config\App;
use Config\Services;
use Tests\Support\DatabaseTestCase;

/**
 * 開催者の認証（トークン・合言葉）とベスト回答の確定（1〜3件・再確定不可）。CODING_RULES 13章
 *
 * @internal
 */
final class HostServiceTest extends DatabaseTestCase
{
    /**
     * 開催者トークンをヘッダーに載せたリクエストを現在のリクエストにする
     */
    private function requestWithToken(?string $token): void
    {
        $request = new IncomingRequest(new App(), new SiteURI(new App(), 'host/x'), null, new UserAgent());

        if ($token !== null) {
            $request->setHeader('X-Host-Token', $token);
        }
        Services::injectMock('request', $request);
    }

    public function testTokenAuthorizesOnlyItsOwnOogiri(): void
    {
        $this->requestWithToken(self::HOST_TOKEN);
        $host = service('host');
        $this->assertTrue($host->isAuthorized(service('oogiri')->findBySlug('k7Qm2xPz')));
        $this->assertFalse($host->isAuthorized(service('oogiri')->findBySlug('Hd3kLp9a')));
        $this->assertSame(self::HOST_TOKEN, $host->tokenFromRequest(service('oogiri')->findBySlug('k7Qm2xPz')));
    }

    public function testWithoutTokenIsNotAuthorized(): void
    {
        $this->requestWithToken(null);
        $this->assertFalse(service('host')->isAuthorized(service('oogiri')->findBySlug('k7Qm2xPz')));
        $this->assertSame('', service('host')->tokenFromRequest(service('oogiri')->findBySlug('k7Qm2xPz')));
    }

    public function testRecoverWithPassphraseReissuesToken(): void
    {
        $this->requestWithToken(null);
        $oogiri = service('oogiri')->findBySlug('k7Qm2xPz');
        $token  = service('host')->recover($oogiri, DevSeeder::PASSPHRASE);
        $this->assertNotSame('', $token);
        // 再発行後は古いトークンでは入れず、新しいトークンで入れる
        $this->requestWithToken(self::HOST_TOKEN);
        $this->assertFalse(service('host')->isAuthorized(service('oogiri')->findBySlug('k7Qm2xPz')));
        $this->requestWithToken($token);
        $this->assertTrue(service('host')->isAuthorized(service('oogiri')->findBySlug('k7Qm2xPz')));
    }

    public function testRecoverWithWrongPassphraseFails(): void
    {
        $this->requestWithToken(null);
        $this->expectException(ValidationException::class);
        service('host')->recover(service('oogiri')->findBySlug('k7Qm2xPz'), 'ちがう合言葉');
    }

    public function testRecoverWithoutPassphraseSetIsImpossible(): void
    {
        $this->requestWithToken(null);
        $oogiri = service('oogiri')->findBySlug('k7Qm2xPz');
        $this->db->table('oogiris')->where('id', $oogiri->id)->update(['passphrase_hash' => null]);
        $this->expectException(ValidationException::class);
        service('host')->recover(service('oogiri')->findBySlug('k7Qm2xPz'), DevSeeder::PASSPHRASE);
    }

    public function testDecideRequiresHost(): void
    {
        $this->requestWithToken(null);
        $this->expectException(ForbiddenException::class);
        service('host')->decide(service('oogiri')->findBySlug('Zp3cW9aQ'), [90322]);
    }

    public function testDecideStoresOneToThreeRanksInOrder(): void
    {
        $this->requestWithToken('dev-token-Zp3cW9aQ');
        $oogiri = service('oogiri')->findBySlug('Zp3cW9aQ');
        service('host')->decide($oogiri, ['90341', '90322', '90341']); // 重複は1つに
        $this->seeInDatabase('oogiris', ['id' => 12466, 'status' => OogiriStatus::Decided->value]);
        $this->seeInDatabase('answers', ['id' => 90341, 'rank' => 1]);
        $this->seeInDatabase('answers', ['id' => 90322, 'rank' => 2]);
        $this->assertNotNull(service('oogiri')->findBySlug('Zp3cW9aQ')->decided_at);
        $this->seeInDatabase('events', ['name' => 'decide', 'oogiri_id' => 12466]);
    }

    public function testDecideRejectsZeroOrFourPicks(): void
    {
        $this->requestWithToken('dev-token-Zp3cW9aQ');
        $oogiri = service('oogiri')->findBySlug('Zp3cW9aQ');

        try {
            service('host')->decide($oogiri, []);
            $this->fail('0件は通らないはず');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('ranks', $e->errors);
        }
        $this->expectException(ValidationException::class);
        service('host')->decide($oogiri, [1, 2, 3, 4]);
    }

    public function testDecideTwiceIsRefused(): void
    {
        $this->requestWithToken('dev-token-q8Lr4nTb');

        try {
            service('host')->decide(service('oogiri')->findBySlug('q8Lr4nTb'), [90412]);
            $this->fail('確定済みは通らないはず');
        } catch (AppException $e) {
            $this->assertSame(ErrorCode::AlreadyDecided, $e->errorCode);
        }
    }

    /**
     * 選べない回答を含む確定は、残りだけで確定せずに全体を拒否する（レビュー指摘 1・読みやすさ 5）
     */
    public function testHiddenAnswerIsRejectedAndNothingIsSaved(): void
    {
        $this->requestWithToken('dev-token-Zp3cW9aQ');
        $this->db->table('answers')->where('id', 90341)->update(['status' => 'hidden']);

        try {
            service('host')->decide(service('oogiri')->findBySlug('Zp3cW9aQ'), [90341, 90322]);
            $this->fail('非表示の回答を含む確定は通らないはず');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('ranks', $e->errors);
        }
        $this->seeInDatabase('oogiris', ['id' => 12466, 'status' => OogiriStatus::AwaitingSelection->value, 'decided_at' => null]);
        $this->seeInDatabase('answers', ['id' => 90322, 'rank' => null]);
    }

    /**
     * 別の大喜利の回答IDでは確定できない（レビュー指摘 1 の再現手順：12466 に 12480 の回答 90412）
     */
    public function testAnswerOfAnotherOogiriIsRejected(): void
    {
        $this->requestWithToken('dev-token-Zp3cW9aQ');

        try {
            service('host')->decide(service('oogiri')->findBySlug('Zp3cW9aQ'), [90412]);
            $this->fail('別の大喜利の回答は通らないはず');
        } catch (ValidationException) {
        }
        $this->seeInDatabase('oogiris', ['id' => 12466, 'status' => OogiriStatus::AwaitingSelection->value]);
        $this->seeInDatabase('answers', ['id' => 90412, 'rank' => null]);
        // 拒否されたあと、正しい回答で確定し直せる
        service('host')->decide(service('oogiri')->findBySlug('Zp3cW9aQ'), [90322]);
        $this->seeInDatabase('oogiris', ['id' => 12466, 'status' => OogiriStatus::Decided->value]);
    }

    /**
     * 同時に始まった確定（同じ未確定の状態を読んだ2つの処理）は、後の方が先の結果を上書きしない（レビュー指摘 4）
     */
    public function testStaleDecideDoesNotOverwriteEarlierResult(): void
    {
        $this->requestWithToken('dev-token-Zp3cW9aQ');
        $first  = service('oogiri')->findBySlug('Zp3cW9aQ');
        $second = service('oogiri')->findBySlug('Zp3cW9aQ'); // どちらも awaiting_selection のまま読み込んだ Entity
        service('host')->decide($first, [90341]);

        try {
            service('host')->decide($second, [90322]);
            $this->fail('後から来た確定は通らないはず');
        } catch (AppException $e) {
            $this->assertSame(ErrorCode::AlreadyDecided, $e->errorCode);
        }
        $this->seeInDatabase('answers', ['id' => 90341, 'rank' => 1]);
        $this->seeInDatabase('answers', ['id' => 90322, 'rank' => null]);
    }

    /**
     * 合言葉の試行回数は Cookie（ブラウザID）を変えても戻らない（レビュー指摘 3）
     */
    public function testPassphraseLimitSurvivesCookieChange(): void
    {
        $this->requestWithToken(null);

        for ($i = 0; $i < RateLimitService::PASSPHRASE_ATTEMPTS; $i++) {
            try {
                service('host')->recover(service('oogiri')->findBySlug('k7Qm2xPz'), 'ちがう' . $i);
            } catch (ValidationException) {
            }
        }
        $_COOKIE[BrowserId::COOKIE_NAME] = 'another-browser';
        $this->requestWithToken(null);

        try {
            service('host')->recover(service('oogiri')->findBySlug('k7Qm2xPz'), DevSeeder::PASSPHRASE);
            $this->fail('Cookie を変えても上限は戻らないはず');
        } catch (AppException $e) {
            $this->assertSame(ErrorCode::RateLimited, $e->errorCode);
        }
    }
}
