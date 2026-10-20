<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OogiriStatus;
use CodeIgniter\I18n\Time;
use Tests\Support\DatabaseTestCase;

/**
 * 状態遷移 S-01（募集終了 → 結果選択待ち）と S-02（期限切れ → 結果未選出）。CODING_RULES 13章
 *
 * @internal
 */
final class LifecycleServiceTest extends DatabaseTestCase
{
    public function testOpenOogiriIsNotClosedBeforeEndsAt(): void
    {
        $this->assertSame(0, service('lifecycle')->closeEnded(Time::now()));
        $this->seeInDatabase('oogiris', ['id' => 12480, 'status' => OogiriStatus::Open->value]);
    }

    public function testOpenOogiriBecomesAwaitingAfterEndsAtWithDeadline(): void
    {
        // DevSeeder の募集中 3 件（k7Qm2xPz・Hd3kLp9a・Ee4fGg5h）はいずれも ends_at が未来。2日後にはすべて終了している
        $count = service('lifecycle')->closeEnded(Time::now()->addDays(2));
        $this->assertSame(3, $count);
        $oogiri = service('oogiri')->findBySlug('k7Qm2xPz');
        $this->assertSame(OogiriStatus::AwaitingSelection, $oogiri->status());
        // 選出期限 = ends_at + select_hours（既定 72 時間）
        $this->assertSame($oogiri->ends_at->addHours(72)->toDateTimeString(), $oogiri->selection_deadline->toDateTimeString());
        $this->seeInDatabase('admin_logs', ['target_type' => 'oogiri', 'target_id' => 12480, 'what' => '募集終了 → 結果選択待ち']);
    }

    public function testAwaitingOogiriExpiresToNoResultAfterDeadline(): void
    {
        $this->assertSame(0, service('lifecycle')->expireUnselected(Time::now()));
        // 選択待ちは Zp3cW9aQ・Mn6bXs4e の 2 件（期限は ends_at + 72h。10日後には過ぎている）
        $count = service('lifecycle')->expireUnselected(Time::now()->addDays(10));
        $this->assertSame(2, $count);
        $this->seeInDatabase('oogiris', ['id' => 12466, 'status' => OogiriStatus::NoResult->value]);
        $this->seeInDatabase('oogiris', ['id' => 12460, 'status' => OogiriStatus::NoResult->value]);
        $this->assertNotNull(service('oogiri')->findBySlug('Zp3cW9aQ')->decided_at);
    }

    public function testTransitionsAreIdempotent(): void
    {
        $later = Time::now()->addDays(2);
        service('lifecycle')->closeEnded($later);
        $this->assertSame(0, service('lifecycle')->closeEnded($later));
    }
}
