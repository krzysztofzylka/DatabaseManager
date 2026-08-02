<?php

namespace Tests\Unit\Helper;

use krzysztofzylka\DatabaseManager\Helper\SqlExpr;
use PHPUnit\Framework\TestCase;

class SqlExprTest extends TestCase
{
    public function testRawReturnsSqlExprInstance(): void
    {
        $expr = SqlExpr::raw('COUNT(*)');

        $this->assertInstanceOf(SqlExpr::class, $expr);
    }

    public function testToStringReturnsRawExpression(): void
    {
        $expr = SqlExpr::raw('NOW()');

        $this->assertSame('NOW()', (string)$expr);
        $this->assertSame('NOW()', $expr->__toString());
    }

    public function testInterpolatesInStrings(): void
    {
        $expr = SqlExpr::raw('MAX(price)');

        $this->assertSame('value: MAX(price)', "value: $expr");
    }
}
