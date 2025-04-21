<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\Condition;
use PHPUnit\Framework\TestCase;

class ConditionTest extends TestCase
{
    public function testBasicCondition(): void
    {
        $condition = new Condition('age', '>', 18);
        $this->assertEquals('`age` > 18', (string)$condition);
    }

    public function testEqualityCondition(): void
    {
        $condition = new Condition('name', '=', 'John');
        $this->assertEquals('`name` = "John"', (string)$condition);
    }

    public function testInCondition(): void
    {
        $condition = new Condition('status', 'IN', ['active', 'pending']);
        $this->assertEquals('`status` IN (\'active\', \'pending\')', (string)$condition);
    }

    public function testNullCondition(): void
    {
        $condition = new Condition('deleted_at', 'IS', null);
        $this->assertEquals('`deleted_at` IS NULL', (string)$condition);
    }

    public function testColumnWithTable(): void
    {
        $condition = new Condition('users.email', 'LIKE', '%example.com%');
        $this->assertEquals('`users`.`email` LIKE "%example.com%"', (string)$condition);
    }

    public function testGetColumnRaw(): void
    {
        $condition = new Condition('users.email', '=', 'test@example.com');
        $this->assertEquals('users.email', $condition->getColumn(true));
    }

    public function testGetColumnFormatted(): void
    {
        $condition = new Condition('users.email', '=', 'test@example.com');
        $this->assertEquals('`users`.`email`', $condition->getColumn());
    }

    public function testGetOperator(): void
    {
        $condition = new Condition('age', '>=', 21);
        $this->assertEquals('>=', $condition->getOperator());
    }

    public function testGetValue(): void
    {
        $value = ['active', 'pending'];
        $condition = new Condition('status', 'IN', $value);
        $this->assertSame($value, $condition->getValue());
    }

    public function testSetValue(): void
    {
        $condition = new Condition('age', '>', 18);
        $condition->setValue(21);
        $this->assertEquals(21, $condition->getValue());
        $this->assertEquals('`age` > 21', (string)$condition);
    }
}