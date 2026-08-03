<?php

namespace Tests\Unit;

use InvalidArgumentException;
use krzysztofzylka\DatabaseManager\Enum\SortDirection;
use krzysztofzylka\DatabaseManager\Helper\SqlExpr;
use krzysztofzylka\DatabaseManager\OrderBy;
use PHPUnit\Framework\TestCase;

class OrderByTest extends TestCase
{
    public function testColumnDefaultsToAscending(): void
    {
        $this->assertSame('id ASC', (string)OrderBy::column('id'));
    }

    public function testColumnWithExplicitDirection(): void
    {
        $this->assertSame('id DESC', (string)OrderBy::column('id', SortDirection::desc));
    }

    public function testColumnAcceptsQualifiedName(): void
    {
        $this->assertSame('users.name ASC', (string)OrderBy::column('users.name'));
    }

    public function testColumnRejectsInvalidIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        OrderBy::column("id; DROP TABLE users; --");
    }

    public function testExpressionUsesRawSqlExpr(): void
    {
        $orderBy = OrderBy::expression(SqlExpr::raw('COUNT(*)'), SortDirection::desc);

        $this->assertSame('COUNT(*) DESC', (string)$orderBy);
    }
}
