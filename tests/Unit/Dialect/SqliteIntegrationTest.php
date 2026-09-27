<?php

namespace Tests\Unit\Dialect;

use krzysztofzylka\DatabaseManager\Cache;
use krzysztofzylka\DatabaseManager\Columns\IdColumn;
use krzysztofzylka\DatabaseManager\Columns\VarcharColumn;
use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\CreateIndex;
use krzysztofzylka\DatabaseManager\CreateTable;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Table;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Runs the generated SQL against a real in-memory SQLite database
 */
class SqliteIntegrationTest extends TestCase
{

    protected function setUp(): void
    {
        Cache::clearAllCache();

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        (new DatabaseManager())->connect(DatabaseConnect::create()->setType(DatabaseType::sqlite)->setConnection($pdo));

        (new CreateTable('users'))
            ->addColumn(new IdColumn())
            ->addColumn((new VarcharColumn('email', 100, false))->setExtra('UNIQUE'))
            ->addColumn(new VarcharColumn('name', 50))
            ->execute();

        (new CreateTable('roles'))
            ->addColumn(new IdColumn())
            ->addColumn(new VarcharColumn('label', 20))
            ->execute();
    }

    public function testIdColumnAutoincrementsAndInsertReturnsTheId(): void
    {
        $table = new Table('users');

        $this->assertTrue($table->insert(['email' => 'a@example.com', 'name' => 'Ann']));
        $this->assertSame(1, $table->getId());

        $this->assertTrue($table->insert(['email' => 'b@example.com', 'name' => 'Bob']));
        $this->assertSame(2, $table->getId());
    }

    public function testExistsMatchesTheExactTableNameOnly(): void
    {
        $this->assertTrue((new Table('users'))->exists());
        $this->assertFalse((new Table('user'))->exists());
    }

    public function testIsColumnUniqueReadsUniqueConstraints(): void
    {
        $table = new Table('users');

        $this->assertTrue($table->isColumnUnique('email'));
        $this->assertFalse($table->isColumnUnique('name'));
        $this->assertFalse($table->isColumnUnique('id'));
    }

    public function testCreateIndexIsQuotedAndAccepted(): void
    {
        $this->assertTrue(CreateIndex::create('users')->addColumn('name')->execute());
        $this->assertSame('CREATE INDEX `index_users_name` ON `users`(`name`)', DatabaseManager::getLastSql());
    }

    public function testJoinWithAliasRunsAndNestsTheAliasedRows(): void
    {
        (new Table('roles'))->insert(['label' => 'admin']);
        (new Table('users'))->insert(['email' => 'a@example.com', 'name' => 'Ann']);

        $rows = (new Table('users'))
            ->bind(BindType::leftJoin, 'roles', 'users.id', 'r.id', new Condition('r.label', '=', 'admin'), 'r')
            ->findAll();

        $this->assertSame('Ann', $rows[0]['users']['name']);
        $this->assertSame('admin', $rows[0]['r']['label']);
    }

    public function testTablesAndColumnsAreListed(): void
    {
        $this->assertContains('users', DatabaseManager::getTables());
        $this->assertSame(['id', 'email', 'name'], array_column((new Table('users'))->columnList(), 'Field'));
    }

}
