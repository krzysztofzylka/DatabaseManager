<?php

namespace Tests\Unit\Enum;

use krzysztofzylka\DatabaseManager\Enum\Trigger;
use PHPUnit\Framework\TestCase;

class TriggerTest extends TestCase
{
    public function testGenerateUpdateTimestampAfterUpdate(): void
    {
        $sql = Trigger::UpdateTimestampAfterUpdate->generate('users', 'date_modify');

        $expectedHash = md5('usersdate_modify');

        $this->assertStringContainsString('CREATE TRIGGER `trigger_audou_' . $expectedHash . '`', $sql);
        $this->assertStringContainsString('BEFORE UPDATE ON `users`', $sql);
        $this->assertStringContainsString('SET NEW.`date_modify` = CURRENT_TIMESTAMP;', $sql);
    }

    public function testGenerateIsDeterministicForSameInput(): void
    {
        $first = Trigger::UpdateTimestampAfterUpdate->generate('orders', 'updated_at');
        $second = Trigger::UpdateTimestampAfterUpdate->generate('orders', 'updated_at');

        $this->assertSame($first, $second);
    }

    public function testGenerateDiffersPerTableAndColumn(): void
    {
        $a = Trigger::UpdateTimestampAfterUpdate->generate('orders', 'updated_at');
        $b = Trigger::UpdateTimestampAfterUpdate->generate('invoices', 'updated_at');

        $this->assertNotSame($a, $b);
    }
}
