<?php

namespace Tests\Unit\Dialect;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Columns\IdColumn;
use krzysztofzylka\DatabaseManager\Columns\VarcharColumn;
use krzysztofzylka\DatabaseManager\ConnectionManager;
use krzysztofzylka\DatabaseManager\CreateTable;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Dialect\MysqlDialect;
use krzysztofzylka\DatabaseManager\Dialect\PostgresDialect;
use krzysztofzylka\DatabaseManager\Dialect\SqliteDialect;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Table;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseDialectTest extends TestCase
{

    public function testEachDatabaseTypeResolvesToItsOwnSharedDialect(): void
    {
        $this->assertInstanceOf(MysqlDialect::class, DatabaseType::mysql->getDialect());
        $this->assertInstanceOf(SqliteDialect::class, DatabaseType::sqlite->getDialect());
        $this->assertInstanceOf(PostgresDialect::class, DatabaseType::postgres->getDialect());
        $this->assertSame(DatabaseType::mysql->getDialect(), DatabaseType::mysql->getDialect());
    }

    public function testQuoteIdentifierQuotesEverySegmentOfAQualifiedName(): void
    {
        $this->assertSame('`users`.`id`', DatabaseType::mysql->getDialect()->quoteIdentifier('users.id'));
        $this->assertSame('`users`', DatabaseType::sqlite->getDialect()->quoteIdentifier('users'));
        $this->assertSame('"public"."users"."id"', DatabaseType::postgres->getDialect()->quoteIdentifier('public.users.id'));
    }

    public function testStringDefaultEscapesSingleQuotesTheSameWayOnEveryEngine(): void
    {
        $column = (new Column('bio', ColumnType::text, null))->setDefault("it's");

        $this->assertSame("`bio` TEXT DEFAULT 'it''s'", DatabaseType::mysql->getDialect()->columnDefinition($column));
        $this->assertSame("`bio` TEXT DEFAULT 'it''s'", DatabaseType::sqlite->getDialect()->columnDefinition($column));
        $this->assertSame("\"bio\" TEXT DEFAULT 'it''s'", DatabaseType::postgres->getDialect()->columnDefinition($column));
    }

    public function testOnlyMysqlDeclaresThePrimaryKeyAtTableLevel(): void
    {
        $column = new IdColumn();

        $this->assertSame('PRIMARY KEY (`id`)', DatabaseType::mysql->getDialect()->primaryKeyClause($column));
        $this->assertNull(DatabaseType::sqlite->getDialect()->primaryKeyClause($column));
        $this->assertNull(DatabaseType::postgres->getDialect()->primaryKeyClause($column));
    }

    public function testPostgresAutoincrementPrimaryKeyKeepsThePrimaryKey(): void
    {
        $this->assertSame(
            '"id" BIGSERIAL NOT NULL PRIMARY KEY',
            DatabaseType::postgres->getDialect()->columnDefinition(new IdColumn())
        );
    }

    public function testCreateTableOnNamedConnectionUsesThatConnectionsDialectNotTheDefaults(): void
    {
        $mysqlPdo = $this->createStub(PDO::class);
        (new DatabaseManager())->connect(DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($mysqlPdo));

        $sqlitePdo = $this->createMock(PDO::class);
        $sqlitePdo->expects($this->once())
            ->method('exec')
            ->with('CREATE TABLE `items` (`id` INTEGER PRIMARY KEY AUTOINCREMENT, `name` VARCHAR(20) NULL)');
        ConnectionManager::addConnection('lite', DatabaseConnect::create()->setType(DatabaseType::sqlite)->setConnection($sqlitePdo));

        (new CreateTable('items', 'lite'))
            ->addColumn(new IdColumn())
            ->addColumn(new VarcharColumn('name', 20))
            ->execute();
    }

    public function testIsColumnUniqueRejectsAnInvalidColumnName(): void
    {
        (new DatabaseManager())->connect(
            DatabaseConnect::create()->setType(DatabaseType::postgres)->setConnection($this->createStub(PDO::class))
        );

        $this->expectException(InvalidArgumentException::class);

        (new Table('users'))->isColumnUnique("name' OR '1'='1");
    }

}
