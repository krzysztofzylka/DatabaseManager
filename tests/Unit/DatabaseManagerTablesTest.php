<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class DatabaseManagerTablesTest extends TestCase
{
    private function connectWithMockPdo(PDO $pdo, DatabaseType $type): void
    {
        $connect = DatabaseConnect::create()
            ->setType($type)
            ->setConnection($pdo);

        (new DatabaseManager())->connect($connect);
    }

    private function statementFetchingNumRows(array $rows): PDOStatement
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('fetch')->willReturnOnConsecutiveCalls(...[...$rows, false]);

        return $statement;
    }

    public function testGetTablesOnMysqlUsesShowTables(): void
    {
        $statement = $this->statementFetchingNumRows([['items'], ['categories']]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('query')->with('SHOW TABLES')->willReturn($statement);

        $this->connectWithMockPdo($pdo, DatabaseType::mysql);

        $this->assertSame(['items', 'categories'], DatabaseManager::getTables());
    }

    public function testGetTablesOnSqliteQueriesSqliteMaster(): void
    {
        $statement = $this->statementFetchingNumRows([['items']]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with($this->stringContains('sqlite_master'))
            ->willReturn($statement);

        $this->connectWithMockPdo($pdo, DatabaseType::sqlite);

        $this->assertSame(['items'], DatabaseManager::getTables());
    }

    public function testGetTablesReturnsEmptyArrayWhenQueryFails(): void
    {
        $pdo = $this->createStub(PDO::class);
        $pdo->method('query')->willReturn(false);

        $this->connectWithMockPdo($pdo, DatabaseType::mysql);

        $this->assertSame([], DatabaseManager::getTables());
    }

    public function testGetTablesOnPostgresUsesInformationSchema(): void
    {
        $statement = $this->statementFetchingNumRows([['items']]);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('query')->willReturn($statement);

        $this->connectWithMockPdo($pdo, DatabaseType::postgres);

        $this->assertSame(['items'], DatabaseManager::getTables());
    }

    public function testGetTableDetailsOnMysqlGroupsRowsByTableName(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('fetch')->willReturnOnConsecutiveCalls(
            ['TABLE_NAME' => 'items', 'COLUMN_NAME' => 'id'],
            ['TABLE_NAME' => 'items', 'COLUMN_NAME' => 'name'],
            false
        );

        $pdo = $this->createStub(PDO::class);
        $pdo->method('query')->willReturn($statement);

        $this->connectWithMockPdo($pdo, DatabaseType::mysql);

        $details = DatabaseManager::getTableDetails();

        $this->assertCount(2, $details['items']);
        $this->assertSame('id', $details['items'][0]['COLUMN_NAME']);
        $this->assertSame('name', $details['items'][1]['COLUMN_NAME']);
    }

    public function testGetTableDetailsOnSqliteReadsPragmaPerTable(): void
    {
        $tableListStatement = $this->createStub(PDOStatement::class);
        $tableListStatement->method('fetch')->willReturnOnConsecutiveCalls(['items'], false);

        $columnsStatement = $this->createStub(PDOStatement::class);
        $columnsStatement->method('fetch')->willReturnOnConsecutiveCalls(
            ['name' => 'id', 'type' => 'INTEGER', 'notnull' => 1, 'dflt_value' => null, 'pk' => 1],
            false
        );

        $pdo = $this->createStub(PDO::class);
        $pdo->method('query')->willReturnOnConsecutiveCalls($tableListStatement, $columnsStatement);

        $this->connectWithMockPdo($pdo, DatabaseType::sqlite);

        $details = DatabaseManager::getTableDetails();

        $this->assertSame([
            'items' => [
                [
                    'column_name' => 'id',
                    'column_type' => 'INTEGER',
                    'is_nullable' => 'NO',
                    'default_value' => null,
                    'is_primary_key' => 'YES',
                ],
            ],
        ], $details);
    }
}
