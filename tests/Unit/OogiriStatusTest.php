<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OogiriStatus;
use CodeIgniter\Test\CIUnitTestCase;

/** 募集URLの出し分け（FEATURES 0-4）と回答受付の判定 */
final class OogiriStatusTest extends CIUnitTestCase
{
    public function testScreenBySatus(): void
    {
        $this->assertSame('U-04', OogiriStatus::Open->screen());
        $this->assertSame('U-11', OogiriStatus::AwaitingSelection->screen());
        $this->assertSame('U-10', OogiriStatus::Decided->screen());
        $this->assertSame('U-12', OogiriStatus::NoResult->screen());
        $this->assertSame('U-13', OogiriStatus::Hidden->screen());
        $this->assertSame('U-13', OogiriStatus::Deleted->screen());
    }

    public function testOnlyOpenAcceptsAnswers(): void
    {
        foreach (OogiriStatus::cases() as $status) {
            $this->assertSame($status === OogiriStatus::Open, $status->acceptsAnswers(), $status->value);
        }
    }
}
