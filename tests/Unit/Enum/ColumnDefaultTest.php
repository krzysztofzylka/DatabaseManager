<?php

namespace Tests\Unit\Enum;

use krzysztofzylka\DatabaseManager\Enum\ColumnDefault;
use PHPUnit\Framework\TestCase;

class ColumnDefaultTest extends TestCase
{
    public function testCurrentTimestampValue(): void
    {
        $this->assertSame('CURRENT_TIMESTAMP', ColumnDefault::currentTimestamp->value);
    }

    public function testFromValue(): void
    {
        $this->assertSame(ColumnDefault::currentTimestamp, ColumnDefault::from('CURRENT_TIMESTAMP'));
    }
}
