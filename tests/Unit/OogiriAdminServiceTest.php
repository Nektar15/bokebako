<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entities\AdminUser;
use App\Enums\AdminRole;
use App\Enums\OgpKind;
use App\Enums\OogiriStatus;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use CodeIgniter\I18n\Time;
use Tests\Support\DatabaseTestCase;

/**
 * 管理者の操作（A-03・A-04）：代理でのベスト回答変更、再公開、非表示・削除と OGP 画像
 *
 * @internal
 */
final class OogiriAdminServiceTest extends DatabaseTestCase
{
    /**
     * @var list<string> テストで置いた OGP 画像（後片付け用）
     */
    private array $placed = [];

    protected function setUp(): void
    {
        parent::setUp();
        service('currentAdmin')->set(new AdminUser(1, 'test-admin', AdminRole::Super));
    }

    protected function tearDown(): void
    {
        foreach ($this->placed as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        Time::setTestNow();
        parent::tearDown();
    }

    public function testAdminCanChangeRanksOfDecidedOogiri(): void
    {
        // 確定済み（q8Lr4nTb = 12452）の順位を差し替える（誤操作対応）
        service('oogiriAdmin')->decide(12452, [88302, 88301]);
        $this->seeInDatabase('answers', ['id' => 88302, 'rank' => 1]);
        $this->seeInDatabase('answers', ['id' => 88301, 'rank' => 2]);
        $this->seeInDatabase('oogiris', ['id' => 12452, 'status' => OogiriStatus::Decided->value]);
    }

    public function testAdminCannotRankAnswerOfAnotherOogiri(): void
    {
        $this->expectException(ValidationException::class);
        service('oogiriAdmin')->decide(12466, [90412]);
    }

    public function testAdminCannotDecideHiddenOogiri(): void
    {
        service('oogiriAdmin')->hide(12466);
        $this->expectException(AppException::class);
        service('oogiriAdmin')->decide(12466, [90322]);
    }

    /**
     * 募集中に非表示 → 締切後に再公開：結果選択待ちになり、選出期限が入って S-02 で終われる（レビュー指摘 6）
     */
    public function testRepublishAfterEndsAtSetsSelectionDeadline(): void
    {
        service('oogiriAdmin')->hide(12480);
        Time::setTestNow(Time::now()->addDays(2));
        service('oogiriAdmin')->publish(12480);

        $oogiri = service('oogiri')->findBySlug('k7Qm2xPz');
        $this->assertSame(OogiriStatus::AwaitingSelection, $oogiri->status());
        $this->assertNotNull($oogiri->selection_deadline);
        $this->assertSame(service('lifecycle')->selectionDeadline($oogiri)->toDateTimeString(), $oogiri->selection_deadline->toDateTimeString());
        // 選出期限を過ぎれば、通常どおり結果未選出になる
        service('lifecycle')->expireUnselected(Time::now()->addDays(10));
        $this->seeInDatabase('oogiris', ['id' => 12480, 'status' => OogiriStatus::NoResult->value]);
    }

    /**
     * 非表示・削除で、公開ディレクトリの OGP 画像（募集・結果・回答シェア用）が消える（レビュー指摘 7）
     */
    public function testHidingOogiriRemovesItsOgpImages(): void
    {
        $recruit = $this->placeOgp(OgpKind::Recruit, 'q8Lr4nTb');
        $result  = $this->placeOgp(OgpKind::Result, 'q8Lr4nTb');
        $answer  = $this->placeOgp(OgpKind::Answer, '88301');
        $other   = $this->placeOgp(OgpKind::Answer, '90412'); // 別の大喜利の回答は消さない

        service('oogiriAdmin')->hide(12452);

        $this->assertFileDoesNotExist($recruit);
        $this->assertFileDoesNotExist($result);
        $this->assertFileDoesNotExist($answer);
        $this->assertFileExists($other);
    }

    /**
     * 回答を非表示にすると、その回答のシェア用画像が消え、結果画像は今の公開状態で作り直す（作れなければ古い画像を残さない）
     */
    public function testHidingRankedAnswerRemovesStaleImages(): void
    {
        $first  = service('oogiriAdmin')->ranked(service('oogiriAdmin')->find(12452))[0];
        $answer = $this->placeOgp(OgpKind::Answer, (string) $first->id);
        $result = $this->placeOgp(OgpKind::Result, 'q8Lr4nTb');

        service('answerAdmin')->hide($first->id);

        $this->assertFileDoesNotExist($answer);
        // テスト環境にはフォントがなく作り直しは失敗する。そのときも、非表示にした本文が載った古い画像は残らない
        if (is_file($result)) {
            $this->assertNotSame('stale', file_get_contents($result));
        }
        $this->assertNotSame($first->id, service('ogp')->firstRanked(service('oogiriAdmin')->find(12452))?->id);
    }

    private function placeOgp(OgpKind $kind, string $key): string
    {
        $path = service('ogp')->path($kind, $key);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        file_put_contents($path, 'stale');
        $this->placed[] = $path;

        return $path;
    }
}
