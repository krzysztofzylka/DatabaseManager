<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\CreateTable;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TablePredefinedColumnTest extends TestCase
{
    private CreateTable $createTable;

    protected function setUp(): void
    {
        $pdo = $this->createStub(PDO::class);
        $connect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        $this->createTable = (new CreateTable())->setName('test_table');
    }

    private function firstColumnSql(): string
    {
        $property = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $property->setAccessible(true);

        return $property->getValue($this->createTable)[0];
    }

    public function testAddUsernameColumn(): void
    {
        $this->createTable->addUsernameColumn(false, 'login', 64);
        $sql = $this->firstColumnSql();

        $this->assertStringContainsString('`login`', $sql);
        $this->assertStringContainsString('VARCHAR(64)', $sql);
        $this->assertStringContainsString('NOT NULL', $sql);
    }

    public function testAddPasswordColumn(): void
    {
        $this->createTable->addPasswordColumn();
        $sql = $this->firstColumnSql();

        $this->assertStringContainsString('`password`', $sql);
        $this->assertStringContainsString('VARCHAR(255)', $sql);
    }

    public function testAddPhoneColumn(): void
    {
        $this->createTable->addPhoneColumn(false);
        $sql = $this->firstColumnSql();

        $this->assertStringContainsString('`phone`', $sql);
        $this->assertStringContainsString('INT(26)', $sql);
        $this->assertStringContainsString('NOT NULL', $sql);
    }

    public function testAddDateModifyColumnAddsTriggerOnExecute(): void
    {
        $this->createTable->addDateModifyColumn();
        $sql = $this->firstColumnSql();

        $this->assertStringContainsString('`date_modify`', $sql);
        $this->assertStringContainsString('DATETIME', $sql);
        $this->assertStringContainsString('DEFAULT CURRENT_TIMESTAMP', $sql);
    }

    public function testAddSimpleFloatColumn(): void
    {
        $this->createTable->addSimpleFloatColumn('rating', '5,2', 4.5);
        $sql = $this->firstColumnSql();

        $this->assertStringContainsString('`rating`', $sql);
        $this->assertStringContainsString('FLOAT(5,2)', $sql);
        $this->assertStringContainsString('DEFAULT 4.5', $sql);
    }

    public function testAddSimpleDecimalColumn(): void
    {
        $this->createTable->addSimpleDecimalColumn('total', '12,4');
        $sql = $this->firstColumnSql();

        $this->assertStringContainsString('`total`', $sql);
        $this->assertStringContainsString('DECIMAL(12,4)', $sql);
    }

    public function testAddSimpleTextColumn(): void
    {
        $this->createTable->addSimpleTextColumn('description', 'n/a');
        $sql = $this->firstColumnSql();

        $this->assertStringContainsString('`description`', $sql);
        $this->assertStringContainsString('TEXT', $sql);
        $this->assertStringContainsString("DEFAULT 'n/a'", $sql);
    }

    public function testAddSimpleDateColumn(): void
    {
        $this->createTable->addSimpleDateColumn('birth_date');
        $sql = $this->firstColumnSql();

        $this->assertStringContainsString('`birth_date`', $sql);
        $this->assertStringContainsString('DATE', $sql);
    }

    public function testAddColumnAcceptsPrebuiltColumnInstance(): void
    {
        $this->createTable->addColumn((new Column('note'))->setType(\krzysztofzylka\DatabaseManager\Enum\ColumnType::text));
        $sql = $this->firstColumnSql();

        $this->assertStringContainsString('`note`', $sql);
        $this->assertStringContainsString('TEXT', $sql);
    }
}
