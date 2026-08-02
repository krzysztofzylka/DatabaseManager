<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Table;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class TableUpdateTest extends TestCase
{
    private function connectWithMockPdo(PDO $pdo, DatabaseType $type = DatabaseType::mysql): void
    {
        $connect = DatabaseConnect::create()
            ->setType($type)
            ->setConnection($pdo);

        (new DatabaseManager())->connect($connect);
    }

    public function testUpdateBuildsSqlAndBindsAllColumns(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->exactly(2))->method('bindValue')->willReturn(true);
        $statement->expects($this->once())->method('execute')->willReturn(true);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $this->connectWithMockPdo($pdo);

        $table = (new Table('items'))->setId(10);

        $this->assertTrue($table->update(['name' => 'John', 'age' => 30]));
        $this->assertSame(
            'UPDATE `items` SET `name` = :name, `age` = :age WHERE id=10',
            DatabaseManager::getLastSql()
        );
    }

    public function testUpdateUsesDoubleQuotesForPostgres(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $this->connectWithMockPdo($pdo, DatabaseType::postgres);

        $table = (new Table('items'))->setId(1);
        $table->update(['name' => 'Jane']);

        $this->assertSame(
            'UPDATE "items" SET "name" = :name WHERE id=1',
            DatabaseManager::getLastSql()
        );
    }

    public function testUpdateWithoutIdThrows(): void
    {
        $this->connectWithMockPdo($this->createStub(PDO::class));

        $table = new Table('items');

        $this->expectException(DatabaseManagerException::class);

        $table->update(['name' => 'John']);
    }

    public function testUpdateWithoutTableNameThrowsBecauseNameIsUninitialized(): void
    {
        // Table::$name is a typed property with no default, so accessing it via
        // getName() before setName() raises an uninitialized-property Error rather
        // than the DatabaseManagerException the is_null($this->getName()) guard implies.
        $this->connectWithMockPdo($this->createStub(PDO::class));

        $table = new Table();
        $table->setId(1);

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('must not be accessed before initialization');

        $table->update(['name' => 'John']);
    }

    public function testUpdateValueBuildsSqlForSingleColumn(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('bindValue')->with(':status', 'active');
        $statement->expects($this->once())->method('execute')->willReturn(true);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $this->connectWithMockPdo($pdo);

        $table = (new Table('items'))->setId(4);

        $this->assertTrue($table->updateValue('status', 'active'));
        $this->assertSame(
            'UPDATE `items` SET `status` = :status WHERE id=4',
            DatabaseManager::getLastSql()
        );
    }

    public function testUpdateValueWithoutIdThrows(): void
    {
        $this->connectWithMockPdo($this->createStub(PDO::class));

        $table = new Table('items');

        $this->expectException(DatabaseManagerException::class);

        $table->updateValue('status', 'active');
    }
}
