<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Table;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class TableCrudTest extends TestCase
{
    private function connectWithMockPdo(PDO $pdo, DatabaseType $type = DatabaseType::mysql): void
    {
        $connect = DatabaseConnect::create()
            ->setType($type)
            ->setConnection($pdo);

        (new DatabaseManager())->connect($connect);
    }

    public function testSetNameEscapesHtmlSpecialCharacters(): void
    {
        $this->connectWithMockPdo($this->createStub(PDO::class));

        $table = new Table();
        $table->setName('<items>');

        $this->assertSame('&lt;items&gt;', $table->getName());
    }

    public function testSetAndGetId(): void
    {
        $this->connectWithMockPdo($this->createStub(PDO::class));

        $table = new Table('items');
        $table->setId(42);

        $this->assertSame(42, $table->getId());

        $table->setId();

        $this->assertNull($table->getId());
    }

    public function testInsertOnMysqlUsesLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('bindValue')->with(':name', 'John');
        $statement->expects($this->once())->method('execute')->willReturn(true);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);
        $pdo->method('lastInsertId')->willReturn('7');

        $this->connectWithMockPdo($pdo);

        $table = new Table('items');

        $this->assertTrue($table->insert(['name' => 'John']));
        $this->assertSame(7, $table->getId());
        $this->assertSame(
            'INSERT INTO `items` (`name`) VALUES (:name)',
            DatabaseManager::getLastSql()
        );
    }

    public function testInsertOnPostgresUsesReturningId(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetch')->willReturn(['id' => 99]);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $this->connectWithMockPdo($pdo, DatabaseType::postgres);

        $table = new Table('items');

        $this->assertTrue($table->insert(['name' => 'John']));
        $this->assertSame(99, $table->getId());
        $this->assertStringContainsString('RETURNING id', DatabaseManager::getLastSql());
    }

    public function testInsertReturnsFalseAndClearsIdWhenExecuteFails(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(false);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $this->connectWithMockPdo($pdo);

        $table = new Table('items');
        $table->setId(5);

        $this->assertFalse($table->insert(['name' => 'John']));
        $this->assertNull($table->getId());
    }

    public function testDeleteUsesProvidedId(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('exec')
            ->with('DELETE FROM `items` WHERE id=5')
            ->willReturn(1);

        $this->connectWithMockPdo($pdo);

        $table = new Table('items');

        $this->assertTrue($table->delete(5));
    }

    public function testDeleteFallsBackToSetIdWhenNoArgumentGiven(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('exec')
            ->with('DELETE FROM `items` WHERE id=3')
            ->willReturn(1);

        $this->connectWithMockPdo($pdo);

        $table = (new Table('items'))->setId(3);

        $this->assertTrue($table->delete());
    }

    public function testDeleteWithoutIdThrows(): void
    {
        $this->connectWithMockPdo($this->createStub(PDO::class));

        $table = new Table('items');

        $this->expectException(DatabaseManagerException::class);

        $table->delete();
    }

    public function testDeleteByConditionsBuildsWhereClauseAndBindsValues(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('execute')->willReturn(true);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM `items` WHERE'))
            ->willReturn($statement);

        $this->connectWithMockPdo($pdo);

        $table = new Table('items');

        $this->assertTrue($table->deleteByConditions(['lock_name' => 'my-lock']));
    }

    public function testDeleteByConditionsAcceptsConditionObjects(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $this->connectWithMockPdo($pdo);

        $table = new Table('database_locks');

        $this->assertTrue($table->deleteByConditions([new Condition('lock_expiration', '<', '2024-01-01')]));
        $this->assertStringContainsString('`lock_expiration` <', DatabaseManager::getLastSql());
    }

    public function testExistsOnMysqlUsesInformationSchema(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('fetchColumn')->willReturn(1);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('information_schema.TABLES'))
            ->willReturn($statement);

        $this->connectWithMockPdo($pdo);

        $table = new Table('items');

        $this->assertTrue($table->exists());
    }

    public function testExistsReturnsFalseWhenTableMissing(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('fetchColumn')->willReturn(false);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $this->connectWithMockPdo($pdo);

        $table = new Table('missing_table');

        $this->assertFalse($table->exists());
    }

    public function testExistsOnSqliteQueriesSqliteMaster(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['sql' => 'CREATE TABLE items (id INTEGER)']]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with($this->stringContains('sqlite_master'))
            ->willReturn($statement);

        $this->connectWithMockPdo($pdo, DatabaseType::sqlite);

        $table = new Table('items');

        $this->assertTrue($table->exists());
    }

    public function testQueryReturnsFetchedRowsAndRecordsLastSql(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['id' => 1], ['id' => 2]]);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('query')->willReturn($statement);

        $this->connectWithMockPdo($pdo);

        $table = new Table('items');
        $rows = $table->query('SELECT * FROM items');

        $this->assertSame([['id' => 1], ['id' => 2]], $rows);
        $this->assertSame('SELECT * FROM items', DatabaseManager::getLastSql());
    }

    public function testGetPdoInstanceReturnsUnderlyingConnection(): void
    {
        $pdo = $this->createStub(PDO::class);
        $this->connectWithMockPdo($pdo);

        $table = new Table('items');

        $this->assertSame($pdo, $table->getPdoInstance());
    }

    public function testBindAndUnbindAndGetBind(): void
    {
        $this->connectWithMockPdo($this->createStub(PDO::class));

        $table = new Table('items');

        $this->assertSame([], $table->getBind());

        $table->bind(\krzysztofzylka\DatabaseManager\Enum\BindType::leftJoin, 'categories');
        $bind = $table->getBind();

        $this->assertCount(1, $bind);
        $this->assertSame('categories', $bind[0]['tableName']);
        $this->assertSame('LEFT JOIN', $bind[0]['type']);

        $table->unbind('categories');

        $this->assertSame([], array_values($table->getBind()));

        $table->bind(\krzysztofzylka\DatabaseManager\Enum\BindType::leftJoin, 'categories');
        $table->unbindAll();

        $this->assertSame([], $table->getBind());
    }
}
