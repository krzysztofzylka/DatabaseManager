<?php

namespace Tests\Unit\Enum;

use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use PHPUnit\Framework\TestCase;

class ColumnTypeEnumTest extends TestCase
{
    public function testGetColumnTypeByNameReturnsMatchingCase(): void
    {
        $this->assertSame(ColumnType::varchar, ColumnType::getColumnTypeByName('varchar'));
        $this->assertSame(ColumnType::bigint, ColumnType::getColumnTypeByName('bigint'));
    }

    public function testGetColumnTypeByNameReturnsNullForUnknownName(): void
    {
        $this->assertNull(ColumnType::getColumnTypeByName('doesNotExist'));
    }
}
