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
        $this->assertEquals('(`name` = :bind_0)', $sql['sql']);
    }

    public function testMultipleAndConditions(): void
    {
        $conditions = [
            'name' => 'John',
            'age' => 30
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`name` = :bind_0 AND `age` = :bind_1)', $sql['sql']);
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
        $this->assertEquals('(`name` = :bind_0 AND (`age` = :bind_1 OR `status` = :bind_2))', $sql['sql']);
    }

    public function testConditionObject(): void
    {
        $conditions = [
            'name' => 'John',
            new Condition('age', '>', 25)
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`name` = :bind_0 AND `age` > 25)', $sql['sql']);
    }

    public function testArrayValues(): void
    {
        $conditions = [
            'status' => ['active', 'pending']
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`status` IN (:bind_0, :bind_1))', $sql['sql']);
        $this->assertEquals([':bind_0' => 'active', ':bind_1' => 'pending'], $sql['bind']);

    }

    public function testBetweenValues(): void
    {
        $conditions = [
            'created_at' => ['BETWEEN', ['2024-01-01', '2024-12-31']]
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`created_at` BETWEEN :bind_0 AND :bind_1)', $sql['sql']);
        $this->assertEquals([':bind_0' => '2024-01-01', ':bind_1' => '2024-12-31'], $sql['bind']);
    }

    public function testNullValue(): void
    {
        $conditions = [
            'deleted_at' => null
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`deleted_at` = :bind_0)', $sql['sql']);
    }

    public function testSpecialIsNullCondition(): void
    {
        $conditions = [
            'deleted_at' => 'IS NULL'
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`deleted_at` = :bind_0)', $sql['sql']);
    }

    public function testColumnWithTableName(): void
    {
        $conditions = [
            'users.name' => 'John'
        ];
        $sql = $this->where->getPrepareConditions($conditions);
        $this->assertEquals('(`users`.`name` = :bind_0)', $sql['sql']);
    }

    public function testOrCondition(): void
    {
        $conditions = [
            'name' => 'John',
            'email' => 'john@example.com'
        ];
        $sql = $this->where->getPrepareConditions($conditions, 'OR');
        $this->assertEquals('(`name` = :bind_0 OR `email` = :bind_1)', $sql['sql']);
    }
}