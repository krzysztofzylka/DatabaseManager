<?php

namespace Tests\Unit;

use InvalidArgumentException;
use krzysztofzylka\DatabaseManager\GroupBy;
use krzysztofzylka\DatabaseManager\Helper\SqlExpr;
use PHPUnit\Framework\TestCase;

class GroupByTest extends TestCase
{
    public function testColumn(): void
    {
        $this->assertSame('status', (string)GroupBy::column('status'));
    }

    public function testColumnAcceptsQualifiedName(): void
    {
        $this->assertSame('orders.status', (string)GroupBy::column('orders.status'));
    }

    public function testColumnRejectsInvalidIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        GroupBy::column("status; DROP TABLE users; --");
    }

    public function testExpressionUsesRawSqlExpr(): void
    {
        $groupBy = GroupBy::expression(SqlExpr::raw('DATE(created_at)'));

        $this->assertSame('DATE(created_at)', (string)$groupBy);
    }
}
