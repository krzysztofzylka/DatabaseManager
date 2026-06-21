<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\ConnectionManager;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Table;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class TableSelectCountTest extends TestCase
{
    private function connectWithMockPdo(PDOStatement $statement): PDO
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $connect = DatabaseConnect::create()
            ->setType(DatabaseType::mysql)
            ->setConnection($pdo);

        (new DatabaseManager())->connect($connect);
        ConnectionManager::setDefaultConnectionObject($connect);

        return $pdo;
    }

    public function testFindCountWithoutGroupByUsesSimpleCount(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetch')->willReturn(['count' => 5]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');

        $this->assertSame(5, $table->findCount());
        $this->assertSame(
            'SELECT COUNT(*) as `count` FROM `items`',
            DatabaseManager::getLastSql()
        );
    }

    public function testFindCountWithGroupByCountsGroupsNotRows(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetch')->willReturn(['count' => 3]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');

        $this->assertSame(3, $table->findCount(null, 'category'));
        $this->assertSame(
            'SELECT COUNT(*) as `count` FROM (SELECT 1 FROM `items`  GROUP BY `category`) as `grouped_count`',
            DatabaseManager::getLastSql()
        );
    }
}
