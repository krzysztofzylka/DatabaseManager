<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Helper\SqlExpr;
use krzysztofzylka\DatabaseManager\Table;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class TableSelectFindTest extends TestCase
{
    private function connectWithMockPdo(PDOStatement $statement): PDO
    {
        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $connect = DatabaseConnect::create()
            ->setType(DatabaseType::mysql)
            ->setConnection($pdo);

        (new DatabaseManager())->connect($connect);

        return $pdo;
    }

    public function testFindReturnsNestedRowUnderTableName(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetch')->willReturn(['items.id' => 1, 'items.name' => 'Widget']);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');
        $result = $table->find(['id' => 1], ['id', 'name']);

        $this->assertSame(['items' => ['id' => 1, 'name' => 'Widget']], $result);
        $this->assertStringContainsString('SELECT', DatabaseManager::getLastSql());
        $this->assertStringContainsString('LIMIT 1', DatabaseManager::getLastSql());
    }

    public function testFindReturnsEmptyArrayWhenNoRowMatches(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetch')->willReturn(false);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');

        $this->assertSame([], $table->find(['id' => 999], ['id']));
    }

    public function testFindAllReturnsListOfNestedRows(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([
            ['items.id' => 1, 'items.name' => 'Widget'],
            ['items.id' => 2, 'items.name' => 'Gadget'],
        ]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');
        $result = $table->findAll(null, ['id', 'name']);

        $this->assertSame([
            ['items' => ['id' => 1, 'name' => 'Widget']],
            ['items' => ['id' => 2, 'name' => 'Gadget']],
        ], $result);
    }

    public function testFindAllAppliesOrderByAndLimit(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');
        $table->findAll(null, ['id'], 'id', '10');

        $this->assertStringContainsString('ORDER BY', DatabaseManager::getLastSql());
        $this->assertStringContainsString('LIMIT 10', DatabaseManager::getLastSql());
    }

    public function testFindIssetReturnsTrueWhenCountIsPositive(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetch')->willReturn(['count' => 1]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');

        $this->assertTrue($table->findIsset(['id' => 1]));
    }

    public function testFindIssetReturnsFalseWhenCountIsZero(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetch')->willReturn(['count' => 0]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');

        $this->assertFalse($table->findIsset(['id' => 999]));
    }

    public function testFindAllOrderByAppliesBackticksInsideOrderByFunction(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');
        $table->findAll(null, ['id'], 'COUNT(id) DESC');

        $this->assertStringContainsString('ORDER BY COUNT(`id`) DESC', DatabaseManager::getLastSql());
    }

    public function testFindAllGroupByAppliesBackticksToColumnName(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');
        $table->findAll(null, ['id'], null, null, 'category');

        $this->assertStringContainsString('GROUP BY `category`', DatabaseManager::getLastSql());
    }

    public function testFindAllAcceptsSqlExprColumnAndAutoQualifiesAlias(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');
        $table->findAll(null, [SqlExpr::raw('COUNT(*) as total')]);

        $this->assertStringContainsString('COUNT(*) as `items.total`', DatabaseManager::getLastSql());
    }
}
