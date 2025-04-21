<?php

namespace Tests\Unit\Helper;

use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Helper\Where;
use PHPUnit\Framework\TestCase;

class WhereTest extends TestCase
{
    private Where $where;

    protected function setUp(): void
    {
        $this->where = new Where();
    }

    public function testSimpleEquality(): void
    {
        $conditions = ['name' => 'John'];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`name` = "John")', $sql);
    }

    public function testMultipleAndConditions(): void
    {
        $conditions = [
            'name' => 'John',
            'age' => 30
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`name` = "John" AND `age` = 30)', $sql);
    }

    public function testNestedConditions(): void
    {
        $conditions = [
            'name' => 'John',
            'OR' => [
                'age' => 30,
                'status' => 'active'
            ]
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`name` = "John" AND (`age` = 30 OR `status` = "active"))', $sql);
    }

    public function testConditionObject(): void
    {
        $conditions = [
            'name' => 'John',
            new Condition('age', '>', 25)
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`name` = "John" AND `age` > 25)', $sql);
    }

    public function testArrayValues(): void
    {
        $conditions = [
            'status' => ['active', 'pending']
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('((`0` = "active" status `1` = "pending"))', $sql);
    }

    public function testNullValue(): void
    {
        $conditions = [
            'deleted_at' => null
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`deleted_at` = NULL)', $sql);
    }

    public function testSpecialIsNullCondition(): void
    {
        $conditions = [
            'deleted_at' => 'IS NULL'
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`deleted_at` = IS NULL)', $sql);
    }

    public function testColumnWithTableName(): void
    {
        $conditions = [
            'users.name' => 'John'
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`users`.`name` = "John")', $sql);
    }

    public function testOrCondition(): void
    {
        $conditions = [
            'name' => 'John',
            'email' => 'john@example.com'
        ];
        $sql = $this->where->getPrepareConditions($conditions, 'OR');
        $this->assertEquals('(`name` = "John" OR `email` = "john@example.com")', $sql);
    }
}