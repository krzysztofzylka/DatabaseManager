<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\AlterTable;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Column;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AlterTableTest extends TestCase
{
    private function connectWithMockPdo(): PDO
    {
        $pdo = $this->createStub(PDO::class);

        $connect = DatabaseConnect::create()
            ->setType(DatabaseType::mysql)
            ->setConnection($pdo);

        (new DatabaseManager())->connect($connect);

        return $pdo;
    }

    private function getSqlQueue(AlterTable $alterTable): array
    {
        $property = (new ReflectionClass(AlterTable::class))->getProperty('sql');
        $property->setAccessible(true);

        return $property->getValue($alterTable);
    }

    public function testCreateSetsTableName(): void
    {
        $alterTable = AlterTable::create('users');

        $this->assertSame('users', $alterTable->getName());
    }

    public function testSetNameOverridesConstructorName(): void
    {
        $alterTable = new AlterTable('users');
        $alterTable->setName('customers');

        $this->assertSame('customers', $alterTable->getName());
    }

    public function testAddColumnQueuesExpectedSql(): void
    {
        $this->connectWithMockPdo();
        $column = (new Column('phone'))->setType(ColumnType::varchar, 20)->setNull(true);

        $alterTable = AlterTable::create('users')->addColumn($column);
        $sql = $this->getSqlQueue($alterTable);

        $this->assertCount(1, $sql);
        $this->assertStringContainsString('ALTER TABLE `users` ADD', $sql[0]);
        $this->assertStringContainsString('`phone`', $sql[0]);
        $this->assertStringContainsString('VARCHAR(20)', $sql[0]);
    }

    public function testAddColumnWithAfterColumnName(): void
    {
        $this->connectWithMockPdo();
        $column = (new Column('phone'))->setType(ColumnType::varchar, 20)->setNull(true);

        $alterTable = AlterTable::create('users')->addColumn($column, 'email');
        $sql = $this->getSqlQueue($alterTable);

        $this->assertStringContainsString('AFTER `email`', $sql[0]);
        $this->assertStringEndsWith(';', $sql[0]);
    }

    public function testRemoveColumnQueuesExpectedSql(): void
    {
        $alterTable = AlterTable::create('users')->removeColumn('phone');
        $sql = $this->getSqlQueue($alterTable);

        $this->assertSame('ALTER TABLE `users` DROP COLUMN `phone`;', $sql[0]);
    }

    public function testModifyColumnQueuesExpectedSqlWithSizeAndAttributes(): void
    {
        $alterTable = AlterTable::create('users')
            ->modifyColumn('age', ColumnType::int, '11', ['UNSIGNED', 'NOT NULL']);
        $sql = $this->getSqlQueue($alterTable);

        $this->assertSame('ALTER TABLE `users` MODIFY `age` int(11) UNSIGNED NOT NULL;', $sql[0]);
    }

    public function testModifyColumnWithoutSize(): void
    {
        $alterTable = AlterTable::create('users')
            ->modifyColumn('is_active', ColumnType::bool, null);
        $sql = $this->getSqlQueue($alterTable);

        $this->assertSame('ALTER TABLE `users` MODIFY `is_active` bool;', $sql[0]);
    }

    public function testRenameColumnQueuesExpectedSql(): void
    {
        $alterTable = AlterTable::create('users')->renameColumn('phone', 'phone_number');
        $sql = $this->getSqlQueue($alterTable);

        $this->assertSame('ALTER TABLE `users` RENAME COLUMN `phone` TO `phone_number`;', $sql[0]);
    }

    public function testChainedModificationsAreExecutedInOrder(): void
    {
        $alterTable = AlterTable::create('users')
            ->removeColumn('old_column')
            ->renameColumn('phone', 'phone_number');

        $sql = $this->getSqlQueue($alterTable);

        $this->assertCount(2, $sql);
        $this->assertStringContainsString('DROP COLUMN `old_column`', $sql[0]);
        $this->assertStringContainsString('RENAME COLUMN `phone` TO `phone_number`', $sql[1]);
    }

    public function testSetNameRejectsInvalidSqlIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AlterTable::create('users; DROP TABLE users; --');
    }

    public function testRemoveColumnRejectsInvalidSqlIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AlterTable::create('users')->removeColumn("phone' OR '1'='1");
    }

    public function testModifyColumnRejectsInvalidSqlIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AlterTable::create('users')->modifyColumn('age; DROP TABLE users; --', ColumnType::int);
    }

    public function testRenameColumnRejectsInvalidSqlIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AlterTable::create('users')->renameColumn('phone', "phone' OR '1'='1");
    }

    public function testAddColumnRejectsInvalidAfterColumnName(): void
    {
        $this->connectWithMockPdo();
        $column = (new Column('phone'))->setType(ColumnType::varchar, 20)->setNull(true);

        $this->expectException(InvalidArgumentException::class);

        AlterTable::create('users')->addColumn($column, "email' OR '1'='1");
    }

    public function testExecuteWithNoQueuedChangesIsNoOp(): void
    {
        $alterTable = AlterTable::create('users');

        $alterTable->execute();

        $this->addToAssertionCount(1);
    }

    public function testExecuteRunsQueuedSqlAndUpdatesLastSql(): void
    {
        $this->connectWithMockPdo();

        $alterTable = AlterTable::create('users')->removeColumn('phone');
        $alterTable->execute();

        $this->assertSame('ALTER TABLE `users` DROP COLUMN `phone`;', DatabaseManager::getLastSql());
    }
}
