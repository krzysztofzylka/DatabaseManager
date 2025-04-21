<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\CreateTable;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\ColumnDefault;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class CreateTableTest extends TestCase
{
    private CreateTable $createTable;

    protected function setUp(): void
    {
        $this->createTable = new CreateTable();
        $this->createTable->setName('test_table');

        // Mock DatabaseManager::getDatabaseType
        $this->mockDatabaseType();
    }

    private function mockDatabaseType(): void
    {
        // Używamy mock obiektu do symulowania typu bazy danych
        // Tworzymy obiekt połączenia i ustawiamy go w DatabaseManager
        $mockConnection = $this->createMock(\krzysztofzylka\DatabaseManager\DatabaseConnect::class);
        $mockConnection->method('getType')->willReturn(DatabaseType::mysql);

        $reflection = new ReflectionClass(DatabaseManager::class);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);
        $property->setValue(null, $mockConnection);
    }

    public function testSetName(): void
    {
        $this->createTable->setName('new_table_name');

        $reflectionMethod = new ReflectionMethod(CreateTable::class, 'getName');
        $reflectionMethod->setAccessible(true);

        $this->assertEquals('new_table_name', $reflectionMethod->invoke($this->createTable));
    }

    public function testAddColumn(): void
    {
        $column = new Column();
        $column->setName('test_column')
            ->setType(ColumnType::varchar, 255)
            ->setNull(false);

        $this->createTable->addColumn($column);

        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $reflectionProperty->setAccessible(true);
        $columns = $reflectionProperty->getValue($this->createTable);

        $this->assertCount(1, $columns);
        $this->assertStringContainsString('`test_column`', $columns[0]);
        $this->assertStringContainsString('VARCHAR(255)', $columns[0]);
        $this->assertStringContainsString('NOT NULL', $columns[0]);
    }

    public function testAddIdColumn(): void
    {
        $this->createTable->addIdColumn();

        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $reflectionProperty->setAccessible(true);
        $columns = $reflectionProperty->getValue($this->createTable);

        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('primary');
        $reflectionProperty->setAccessible(true);
        $primary = $reflectionProperty->getValue($this->createTable);

        $this->assertCount(1, $columns);
        $this->assertStringContainsString('`id`', $columns[0]);
        $this->assertStringContainsString('INT', $columns[0]);
        $this->assertStringContainsString('UNSIGNED', $columns[0]);
        $this->assertStringContainsString('NOT NULL', $columns[0]);
        $this->assertStringContainsString('AUTO_INCREMENT', $columns[0]);

        $this->assertCount(1, $primary);
        $this->assertEquals('PRIMARY KEY (id)', $primary[0]);
    }

    public function testAddEmailColumn(): void
    {
        $this->createTable->addEmailColumn();

        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $reflectionProperty->setAccessible(true);
        $columns = $reflectionProperty->getValue($this->createTable);

        $this->assertCount(1, $columns);
        $this->assertStringContainsString('`email`', $columns[0]);
        $this->assertStringContainsString('VARCHAR(255)', $columns[0]);
    }

    public function testAddDateCreatedColumn(): void
    {
        $this->createTable->addDateCreatedColumn();

        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $reflectionProperty->setAccessible(true);
        $columns = $reflectionProperty->getValue($this->createTable);

        $this->assertCount(1, $columns);
        $this->assertStringContainsString('`date_created`', $columns[0]);
        $this->assertStringContainsString('DATETIME', $columns[0]);
        $this->assertStringContainsString('DEFAULT CURRENT_TIMESTAMP', $columns[0]);
        $this->assertStringContainsString('NOT NULL', $columns[0]);
    }

    public function testAddMultipleColumns(): void
    {
        $this->createTable->addIdColumn()
            ->addEmailColumn()
            ->addUsernameColumn()
            ->addPasswordColumn()
            ->addDateCreatedColumn()
            ->addDateModifyColumn();

        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $reflectionProperty->setAccessible(true);
        $columns = $reflectionProperty->getValue($this->createTable);

        $this->assertCount(6, $columns);
    }

    public function testAddSimpleVarcharColumn(): void
    {
        $this->createTable->addSimpleVarcharColumn('name', 100, false, 'default_value');

        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $reflectionProperty->setAccessible(true);
        $columns = $reflectionProperty->getValue($this->createTable);

        $this->assertCount(1, $columns);
        $this->assertStringContainsString('`name`', $columns[0]);
        $this->assertStringContainsString('VARCHAR(100)', $columns[0]);
        $this->assertStringContainsString('NOT NULL', $columns[0]);
        $this->assertStringContainsString("DEFAULT 'default_value'", $columns[0]);
    }

    public function testAddSimpleIntColumn(): void
    {
        $this->createTable->addSimpleIntColumn('count', false, true);

        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $reflectionProperty->setAccessible(true);
        $columns = $reflectionProperty->getValue($this->createTable);

        $this->assertCount(1, $columns);
        $this->assertStringContainsString('`count`', $columns[0]);
        $this->assertStringContainsString('INT', $columns[0]);
        $this->assertStringContainsString('NOT NULL', $columns[0]);
        $this->assertStringContainsString('UNSIGNED', $columns[0]);
    }

    public function testAddSimpleBoolColumn(): void
    {
        $this->createTable->addSimpleBoolColumn('active', true);

        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $reflectionProperty->setAccessible(true);
        $columns = $reflectionProperty->getValue($this->createTable);

        $this->assertCount(1, $columns);
        $this->assertStringContainsString('`active`', $columns[0]);
        $this->assertStringContainsString('TINYINT(1)', $columns[0]);
        $this->assertStringContainsString('DEFAULT 1', $columns[0]);
    }

    public function testAddSimpleEnumColumn(): void
    {
        $this->createTable->addSimpleEnumColumn('status', ['active', 'inactive'], 'active');

        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $reflectionProperty->setAccessible(true);
        $columns = $reflectionProperty->getValue($this->createTable);

        $this->assertCount(1, $columns);
        $this->assertStringContainsString('`status`', $columns[0]);
        $this->assertStringContainsString("ENUM('active','inactive')", $columns[0]);
        $this->assertStringContainsString("DEFAULT 'active'", $columns[0]);
    }

    public function testCreateCompleteTable(): void
    {
        $this->createTable->addIdColumn()
            ->addSimpleVarcharColumn('name', 100, false)
            ->addSimpleVarcharColumn('email', 255, false)
            ->addSimpleEnumColumn('status', ['active', 'inactive'], 'active')
            ->addSimpleBoolColumn('verified', false)
            ->addDateCreatedColumn()
            ->addDateModifyColumn();

        $reflectionMethod = new ReflectionMethod(CreateTable::class, 'execute');

        // Metoda execute wykonuje zapytanie SQL, nie testujemy jej bezpośrednio
        // Sprawdzamy tylko czy generowane zapytanie zawiera wszystkie kolumny

        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $reflectionProperty->setAccessible(true);
        $columns = $reflectionProperty->getValue($this->createTable);

        $this->assertCount(7, $columns);
    }
}