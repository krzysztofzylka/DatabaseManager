<?php

namespace Tests\Unit;

use InvalidArgumentException;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Enum\SortDirection;
use krzysztofzylka\DatabaseManager\GroupBy;
use krzysztofzylka\DatabaseManager\OrderBy;
use krzysztofzylka\DatabaseManager\Table;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class TableSelectStructuredOrderTest extends TestCase
{
    private function connectWithMockPdo(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([]);
        $statement->method('fetch')->willReturn(false);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $connect = DatabaseConnect::create()
            ->setType(DatabaseType::mysql)
            ->setConnection($pdo);

        (new DatabaseManager())->connect($connect);
    }

    public function testFindAllAcceptsSingleOrderByObject(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');
        $table->findAll(null, ['id'], OrderBy::column('id', SortDirection::desc));

        $this->assertStringContainsString('ORDER BY `id` DESC', DatabaseManager::getLastSql());
    }

    public function testFindAllAcceptsArrayOfOrderByObjects(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');
        $table->findAll(null, ['id'], [
            OrderBy::column('status'),
            OrderBy::column('id', SortDirection::desc),
        ]);

        $this->assertStringContainsString('ORDER BY `status` ASC, `id` DESC', DatabaseManager::getLastSql());
    }

    public function testFindAllAcceptsGroupByObject(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');
        $table->findAll(null, ['id'], null, null, GroupBy::column('status'));

        $this->assertStringContainsString('GROUP BY `status`', DatabaseManager::getLastSql());
    }

    public function testFindAcceptsOrderByObject(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');
        $table->find(null, ['id'], OrderBy::column('id', SortDirection::desc));

        $this->assertStringContainsString('id DESC', DatabaseManager::getLastSql());
    }

    public function testFindAllLegacyStringOrderByStillWorks(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');
        $table->findAll(null, ['id'], 'id DESC');

        $this->assertStringContainsString('ORDER BY `id` DESC', DatabaseManager::getLastSql());
    }

    public function testFindAllIntegerLimit(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');
        $table->findAll(null, ['id'], null, 25);

        $this->assertStringContainsString('LIMIT 25', DatabaseManager::getLastSql());
    }

    public function testFindAllIntegerLimitWithOffset(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');
        $table->findAll(null, ['id'], null, 25, null, 50);

        $this->assertStringContainsString('LIMIT 25 OFFSET 50', DatabaseManager::getLastSql());
    }

    public function testFindAllLegacyStringLimitStillWorks(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');
        $table->findAll(null, ['id'], null, '20 OFFSET 0');

        $this->assertStringContainsString('LIMIT 20 OFFSET 0', DatabaseManager::getLastSql());
    }

    public function testFindAllRejectsNegativeLimit(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');

        $this->expectException(InvalidArgumentException::class);

        $table->findAll(null, ['id'], null, -1);
    }

    public function testFindAllRejectsNegativeOffset(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');

        $this->expectException(InvalidArgumentException::class);

        $table->findAll(null, ['id'], null, 10, null, -5);
    }

    public function testFindAllRejectsOffsetWithoutLimit(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');

        $this->expectException(InvalidArgumentException::class);

        $table->findAll(null, ['id'], null, null, null, 10);
    }

    public function testFindAllRejectsOffsetCombinedWithStringLimit(): void
    {
        $this->connectWithMockPdo();

        $table = new Table('items');

        $this->expectException(InvalidArgumentException::class);

        $table->findAll(null, ['id'], null, '20', null, 5);
    }
}
