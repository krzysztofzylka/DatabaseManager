<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use PHPUnit\Framework\TestCase;

class ConditionToSqlTest extends TestCase
{
    public function testBindsSimpleValueInsteadOfEmbeddingIt(): void
    {
        $bindIndex = 0;
        $result = (new Condition('age', '>', 18))->toSql($bindIndex);

        $this->assertSame('`age` > :bind_0', $result['sql']);
        $this->assertSame([':bind_0' => 18], $result['bind']);
        $this->assertSame(1, $bindIndex);
    }

    public function testQuotesAndBackslashesInValueDoNotChangeSqlStructure(): void
    {
        $bindIndex = 0;
        $value = 'O\'Brien " \\ backslash';
        $result = (new Condition('name', '=', $value))->toSql($bindIndex);

        $this->assertSame('`name` = :bind_0', $result['sql']);
        $this->assertSame($value, $result['bind'][':bind_0']);
    }

    /**
     * The old Condition::__toString() embedded the value directly into the SQL string.
     * ConditionMethods::prepareValue() wraps in double quotes unless the value contains a
     * double quote, in which case it switches to single quotes without escaping - so a
     * value containing both a bare single quote and a double quote could terminate the
     * string literal early and inject additional SQL. toSql() must bind the value instead,
     * so the payload can never break out of its placeholder no matter what it contains.
     */
    public function testSqlInjectionPayloadStaysAnOrdinaryBoundValue(): void
    {
        $bindIndex = 0;
        $payload = "x\" OR '1'='1";
        $result = (new Condition('name', '=', $payload))->toSql($bindIndex);

        $this->assertSame('`name` = :bind_0', $result['sql']);
        $this->assertSame($payload, $result['bind'][':bind_0']);
    }

    public function testOrOneEqualsOnePayloadStaysAnOrdinaryBoundValue(): void
    {
        $bindIndex = 0;
        $payload = "' OR 1=1 -- ";
        $result = (new Condition('name', '=', $payload))->toSql($bindIndex);

        $this->assertSame('`name` = :bind_0', $result['sql']);
        $this->assertSame($payload, $result['bind'][':bind_0']);
    }

    public function testIsNullDoesNotConsumeABindPlaceholder(): void
    {
        $bindIndex = 0;
        $result = (new Condition('deleted_at', 'IS', null))->toSql($bindIndex);

        $this->assertSame('`deleted_at` IS NULL', $result['sql']);
        $this->assertSame([], $result['bind']);
        $this->assertSame(0, $bindIndex);
    }

    public function testIsNotNullDoesNotConsumeABindPlaceholder(): void
    {
        $bindIndex = 0;
        $result = (new Condition('deleted_at', 'IS NOT', null))->toSql($bindIndex);

        $this->assertSame('`deleted_at` IS NOT NULL', $result['sql']);
        $this->assertSame([], $result['bind']);
    }

    public function testInBindsOnePlaceholderPerElement(): void
    {
        $bindIndex = 0;
        $result = (new Condition('status', 'IN', ['active', 'pending']))->toSql($bindIndex);

        $this->assertSame('`status` IN (:bind_0, :bind_1)', $result['sql']);
        $this->assertSame([':bind_0' => 'active', ':bind_1' => 'pending'], $result['bind']);
        $this->assertSame(2, $bindIndex);
    }

    /**
     * Regression test: toSql() used to build `column IN ()` for an empty array, which is
     * a SQL syntax error on MySQL/PostgreSQL/SQLite. IN with nothing to match can never
     * be true, so it must compile to an always-false fragment instead.
     */
    public function testInWithEmptyArrayIsAlwaysFalseNotASyntaxError(): void
    {
        $bindIndex = 0;
        $result = (new Condition('id', 'IN', []))->toSql($bindIndex);

        $this->assertSame('1 = 0', $result['sql']);
        $this->assertSame([], $result['bind']);
        $this->assertSame(0, $bindIndex);
    }

    public function testNotInWithEmptyArrayIsAlwaysTrueNotASyntaxError(): void
    {
        $bindIndex = 0;
        $result = (new Condition('id', 'NOT IN', []))->toSql($bindIndex);

        $this->assertSame('1 = 1', $result['sql']);
        $this->assertSame([], $result['bind']);
        $this->assertSame(0, $bindIndex);
    }

    public function testInWithMaliciousElementStaysBound(): void
    {
        $bindIndex = 0;
        $result = (new Condition('status', 'IN', ['active', "') OR ('1'='1"]))->toSql($bindIndex);

        $this->assertSame('`status` IN (:bind_0, :bind_1)', $result['sql']);
        $this->assertSame("') OR ('1'='1", $result['bind'][':bind_1']);
    }

    public function testSharedBindIndexAvoidsPlaceholderCollisions(): void
    {
        $bindIndex = 0;
        $first = (new Condition('name', '=', 'John'))->toSql($bindIndex);
        $second = (new Condition('age', '>', 18))->toSql($bindIndex);

        $this->assertSame('`name` = :bind_0', $first['sql']);
        $this->assertSame('`age` > :bind_1', $second['sql']);
        $this->assertSame(2, $bindIndex);
    }

    public function testRejectsOperatorOutsideAllowlist(): void
    {
        $bindIndex = 0;
        $condition = new Condition('id', '1; DROP TABLE users; --', 1);

        $this->expectException(DatabaseManagerException::class);

        $condition->toSql($bindIndex);
    }

    public function testAllowlistedOperatorsAreCaseInsensitive(): void
    {
        $bindIndex = 0;
        $result = (new Condition('email', 'like', '%example.com%'))->toSql($bindIndex);

        $this->assertSame('`email` like :bind_0', $result['sql']);
        $this->assertSame('%example.com%', $result['bind'][':bind_0']);
    }
}
