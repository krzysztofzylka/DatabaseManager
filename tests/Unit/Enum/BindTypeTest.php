<?php

namespace Tests\Unit\Enum;

use krzysztofzylka\DatabaseManager\Enum\BindType;
use PHPUnit\Framework\TestCase;
use ValueError;

class BindTypeTest extends TestCase
{
    public function testCaseValues(): void
    {
        $this->assertSame('INNER JOIN', BindType::innerJoin->value);
        $this->assertSame('LEFT JOIN', BindType::leftJoin->value);
        $this->assertSame('RIGHT JOIN', BindType::rightJoin->value);
        $this->assertSame('CROSS JOIN', BindType::crossJoin->value);
        $this->assertSame('FULL OUTER JOIN', BindType::fullJoin->value);
        $this->assertSame('#HAS_ONE#', BindType::hasOne->value);
        $this->assertSame('#HAS_MANY#', BindType::hasMany->value);
    }

    public function testGetFromNameReturnsMatchingCase(): void
    {
        foreach (BindType::cases() as $case) {
            $this->assertSame($case, BindType::getFromName($case->name));
        }
    }

    public function testGetFromNameThrowsForUnknownName(): void
    {
        $this->expectException(ValueError::class);

        BindType::getFromName('doesNotExist');
    }
}
