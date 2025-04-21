<?php

namespace Tests\Unit\Helper;

use krzysztofzylka\DatabaseManager\Helper\SqlBuilder;
use PHPUnit\Framework\TestCase;

class SqlBuilderTest extends TestCase
{
    public function testBasicSelect(): void
    {
        $sql = SqlBuilder::select('*', 'users');
        $this->assertEquals('SELECT * FROM `users`', $sql);
    }

    public function testSelectWithColumns(): void
    {
        $sql = SqlBuilder::select('id, name, email', 'users');
        $this->assertEquals('SELECT id, name, email FROM `users`', $sql);
    }

    public function testSelectWithJoin(): void
    {
        $sql = SqlBuilder::select(
            'users.id, users.name, orders.id as order_id',
            'users',
            'LEFT JOIN orders ON users.id = orders.user_id'
        );
        $this->assertEquals('SELECT users.id, users.name, orders.id as order_id FROM `users` LEFT JOIN orders ON users.id = orders.user_id', $sql);
    }

    public function testSelectWithWhere(): void
    {
        $sql = SqlBuilder::select(
            'id, name',
            'users',
            null,
            'id > 5'
        );
        $this->assertEquals('SELECT id, name FROM `users` WHERE id > 5', $sql);
    }

    public function testSelectWithAllParameters(): void
    {
        $sql = SqlBuilder::select(
            'id, name, email',
            'users',
            'LEFT JOIN roles ON users.role_id = roles.id',
            'users.active = 1',
            'users.id',
            'users.name ASC',
            '10'
        );
        $this->assertEquals(
            'SELECT id, name, email FROM `users` LEFT JOIN roles ON users.role_id = roles.id WHERE users.active = 1 GROUP BY users.id ORDER BY users.name ASC LIMIT 10',
            $sql
        );
    }

    public function testTableNameWithBackticks(): void
    {
        $sql = SqlBuilder::select('id, name', '`special_table`');
        $this->assertEquals('SELECT id, name FROM `special_table`', $sql);
    }

    public function testWhitespaceHandling(): void
    {
        $sql = SqlBuilder::select(
            'id, name',
            'users',
            '  LEFT JOIN  orders ON users.id = orders.user_id  '
        );
        $this->assertEquals('SELECT id, name FROM `users` LEFT JOIN orders ON users.id = orders.user_id ', $sql);
    }
}