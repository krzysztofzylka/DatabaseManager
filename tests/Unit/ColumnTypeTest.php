<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\CreateTable;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Helper\PrepareColumn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RevisedColumnTypeTest extends TestCase
{
    private Column $column;

    protected function setUp(): void
    {
        $this->column = new Column();

        // Mock połączenia bazodanowego
        $mockConnection = $this->createMock(DatabaseConnect::class);
        $mockConnection->method('getType')->willReturn(DatabaseType::mysql);

        $reflection = new ReflectionClass(DatabaseManager::class);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);
        $property->setValue(null, $mockConnection);
    }

    #[DataProvider('columnTypeProvider')]
    public function testAllColumnTypes(ColumnType $type, $size, string $expectedSql): void
    {
        $this->column->setName('test_column')
            ->setType($type, $size)
            ->setNull(false);

        $sql = PrepareColumn::generateCreateColumnSql($this->column);
        $this->assertStringContainsString($expectedSql, $sql);
    }

    public static function columnTypeProvider(): array
    {
        return [
            // String types
            [ColumnType::char, 10, 'CHAR(10)'],
            [ColumnType::varchar, 255, 'VARCHAR(255)'],
            [ColumnType::binary, 100, 'BINARY(100)'],
            [ColumnType::varbinary, 100, 'VARBINARY(100)'],
            [ColumnType::tinyblob, null, 'TINYBLOB'],
            [ColumnType::tinytext, null, 'TINYTEXT'],
            [ColumnType::text, null, 'TEXT'],
            [ColumnType::blob, null, 'BLOB'],
            [ColumnType::mediumtext, null, 'MEDIUMTEXT'],
            [ColumnType::mediumblob, null, 'MEDIUMBLOB'],
            [ColumnType::longtext, null, 'LONGTEXT'],
            [ColumnType::longblob, null, 'LONGBLOB'],
            [ColumnType::enum, ['a', 'b', 'c'], "ENUM('a','b','c')"],
            [ColumnType::set, ['x', 'y', 'z'], "SET('x','y','z')"],

            // Numeric types
            [ColumnType::bit, 1, 'BIT(1)'],
            [ColumnType::tinyint, 1, 'TINYINT(1)'],
            [ColumnType::bool, null, 'BOOL'],
            [ColumnType::boolean, null, 'BOOLEAN'],
            [ColumnType::smallint, null, 'SMALLINT'],
            [ColumnType::mediumint, null, 'MEDIUMINT'],
            [ColumnType::int, null, 'INT'],
            [ColumnType::integer, null, 'INTEGER'],
            [ColumnType::bigint, null, 'BIGINT'],
            [ColumnType::float, '10,2', 'FLOAT(10,2)'],
            [ColumnType::double, '10,2', 'DOUBLE(10,2)'],
            [ColumnType::decimal, '10,2', 'DECIMAL(10,2)'],
            [ColumnType::dec, '10,2', 'DEC(10,2)'],

            // Date and Time types
            [ColumnType::date, null, 'DATE'],
            [ColumnType::datetime, null, 'DATETIME'],
            [ColumnType::timestamp, null, 'TIMESTAMP'],
            [ColumnType::time, null, 'TIME'],
            [ColumnType::year, null, 'YEAR'],

            // Special types
            [ColumnType::json, null, 'JSON'],
        ];
    }

    #[DataProvider('nullableColumnProvider')]
    public function testNullableColumns(bool $isNull, string $expectedSql): void
    {
        $this->column->setName('nullable_test')
            ->setType(ColumnType::varchar, 100)
            ->setNull($isNull);

        $sql = PrepareColumn::generateCreateColumnSql($this->column);
        $this->assertStringContainsString($expectedSql, $sql);
    }

    public static function nullableColumnProvider(): array
    {
        return [
            [true, 'NULL'],
            [false, 'NOT NULL'],
        ];
    }

    #[DataProvider('defaultValueProvider')]
    public function testDefaultValues($defaultValue, string $expectedSql): void
    {
        $this->column->setName('default_test')
            ->setType(ColumnType::varchar, 100)
            ->setDefault($defaultValue);

        $sql = PrepareColumn::generateCreateColumnSql($this->column);
        $this->assertStringContainsString($expectedSql, $sql);
    }

    public static function defaultValueProvider(): array
    {
        return [
            ['test', "DEFAULT 'test'"],
            [123, 'DEFAULT 123'],
            [null, 'DEFAULT NULL'],
        ];
    }

    public function testUnsignedColumn(): void
    {
        $this->column->setName('unsigned_test')
            ->setType(ColumnType::int)
            ->setUnsigned(true);

        $sql = PrepareColumn::generateCreateColumnSql($this->column);
        $this->assertStringContainsString('UNSIGNED', $sql);
    }

    public function testAutoIncrementColumn(): void
    {
        $this->column->setName('id')
            ->setType(ColumnType::int)
            ->setAutoincrement(true);

        $sql = PrepareColumn::generateCreateColumnSql($this->column);
        $this->assertStringContainsString('AUTO_INCREMENT', $sql);
    }

    public function testCreateTableWithVariousColumnTypes(): void
    {
        $createTable = new CreateTable();
        $createTable->setName('test_all_types');

        // Dodajemy różne typy kolumn
        $createTable->addIdColumn()
            ->addSimpleVarcharColumn('varchar_col', 255)
            ->addSimpleIntColumn('int_col')
            ->addSimpleBoolColumn('bool_col')
            ->addSimpleFloatColumn('float_col', '10,2')
            ->addSimpleDecimalColumn('decimal_col', '10,2')
            ->addSimpleTextColumn('text_col')
            ->addSimpleDateColumn('date_col')
            ->addSimpleEnumColumn('enum_col', ['a', 'b', 'c']);

        // Uzyskanie dostępu do prywatnej właściwości columns
        $reflectionProperty = (new ReflectionClass(CreateTable::class))->getProperty('columns');
        $reflectionProperty->setAccessible(true);
        $columns = $reflectionProperty->getValue($createTable);

        $this->assertCount(9, $columns);

        // Sprawdzamy czy wszystkie typy kolumn są obecne
        $this->assertStringContainsString('INT', $columns[0]);
        $this->assertStringContainsString('VARCHAR(255)', $columns[1]);
        $this->assertStringContainsString('INT', $columns[2]);
        $this->assertStringContainsString('TINYINT(1)', $columns[3]);
        $this->assertStringContainsString('FLOAT(10,2)', $columns[4]);
        $this->assertStringContainsString('DECIMAL(10,2)', $columns[5]);
        $this->assertStringContainsString('TEXT', $columns[6]);
        $this->assertStringContainsString('DATE', $columns[7]);
        $this->assertStringContainsString("ENUM('a','b','c')", $columns[8]);
    }
}