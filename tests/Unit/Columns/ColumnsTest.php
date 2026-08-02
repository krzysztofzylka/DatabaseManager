<?php

namespace Tests\Unit\Columns;

use krzysztofzylka\DatabaseManager\Columns\BigIntColumn;
use krzysztofzylka\DatabaseManager\Columns\BlobColumn;
use krzysztofzylka\DatabaseManager\Columns\BoolColumn;
use krzysztofzylka\DatabaseManager\Columns\DateColumn;
use krzysztofzylka\DatabaseManager\Columns\DateCreatedColumn;
use krzysztofzylka\DatabaseManager\Columns\DateModifyColumn;
use krzysztofzylka\DatabaseManager\Columns\DatetimeColumn;
use krzysztofzylka\DatabaseManager\Columns\DecimalColumn;
use krzysztofzylka\DatabaseManager\Columns\EmailColumn;
use krzysztofzylka\DatabaseManager\Columns\EnumColumn;
use krzysztofzylka\DatabaseManager\Columns\FloatColumn;
use krzysztofzylka\DatabaseManager\Columns\IdColumn;
use krzysztofzylka\DatabaseManager\Columns\IntColumn;
use krzysztofzylka\DatabaseManager\Columns\JsonColumn;
use krzysztofzylka\DatabaseManager\Columns\PasswordColumn;
use krzysztofzylka\DatabaseManager\Columns\PhoneColumn;
use krzysztofzylka\DatabaseManager\Columns\PriceColumn;
use krzysztofzylka\DatabaseManager\Columns\TextColumn;
use krzysztofzylka\DatabaseManager\Columns\UsernameColumn;
use krzysztofzylka\DatabaseManager\Columns\UuidColumn;
use krzysztofzylka\DatabaseManager\Columns\VarcharColumn;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Enum\Trigger;
use PDO;
use PHPUnit\Framework\TestCase;

class ColumnsTest extends TestCase
{
    private function connectWithMockPdo(DatabaseType $type = DatabaseType::mysql): void
    {
        $connect = DatabaseConnect::create()
            ->setType($type)
            ->setConnection($this->createStub(PDO::class));

        (new DatabaseManager())->connect($connect);
    }

    public function testVarcharColumn(): void
    {
        $column = new VarcharColumn('name', 100, false, 'John');

        $this->assertSame('name', $column->getName());
        $this->assertSame(ColumnType::varchar, $column->getType());
        $this->assertSame(100, $column->getTypeSize());
        $this->assertFalse($column->isNull());
        $this->assertSame('John', $column->getDefault());
    }

    public function testIntColumn(): void
    {
        $column = new IntColumn('age', false, true);

        $this->assertSame(ColumnType::int, $column->getType());
        $this->assertFalse($column->isNull());
        $this->assertTrue($column->isUnsigned());
    }

    public function testBigIntColumn(): void
    {
        $column = new BigIntColumn('big_number');

        $this->assertSame(ColumnType::bigint, $column->getType());
        $this->assertTrue($column->isNull());
        $this->assertTrue($column->isUnsigned());
    }

    public function testBoolColumnStoresTinyintWithDefault(): void
    {
        $column = new BoolColumn('is_active', true);

        $this->assertSame(ColumnType::tinyint, $column->getType());
        $this->assertSame(1, $column->getTypeSize());
        $this->assertSame(1, $column->getDefault());
    }

    public function testBoolColumnDefaultsToZero(): void
    {
        $column = new BoolColumn('is_active');

        $this->assertSame(0, $column->getDefault());
    }

    public function testFloatColumn(): void
    {
        $column = new FloatColumn('rating', '4,2', 1.5);

        $this->assertSame(ColumnType::float, $column->getType());
        $this->assertSame('4,2', $column->getTypeSize());
        $this->assertSame(1.5, $column->getDefault());
    }

    public function testDecimalColumn(): void
    {
        $column = new DecimalColumn('total', '12,4');

        $this->assertSame(ColumnType::decimal, $column->getType());
        $this->assertSame('12,4', $column->getTypeSize());
        $this->assertFalse($column->isDefaultDefined());
    }

    public function testPriceColumnIsNotNullByDefault(): void
    {
        $column = new PriceColumn('amount', '10,2', 9.99);

        $this->assertSame(ColumnType::decimal, $column->getType());
        $this->assertFalse($column->isNull());
        $this->assertSame(9.99, $column->getDefault());
    }

    public function testTextColumn(): void
    {
        $column = new TextColumn('description', 'n/a');

        $this->assertSame(ColumnType::text, $column->getType());
        $this->assertSame('n/a', $column->getDefault());
    }

    public function testBlobColumn(): void
    {
        $column = new BlobColumn();

        $this->assertSame('blob', $column->getName());
        $this->assertSame(ColumnType::blob, $column->getType());
        $this->assertTrue($column->isNull());
    }

    public function testJsonColumn(): void
    {
        $column = new JsonColumn('payload', false);

        $this->assertSame(ColumnType::json, $column->getType());
        $this->assertFalse($column->isNull());
    }

    public function testDateColumn(): void
    {
        $column = new DateColumn('birth_date');

        $this->assertSame(ColumnType::date, $column->getType());
        $this->assertFalse($column->isDefaultDefined());
    }

    public function testDatetimeColumn(): void
    {
        $column = new DatetimeColumn('happened_at', '2024-01-01 00:00:00');

        $this->assertSame(ColumnType::datetime, $column->getType());
        $this->assertSame('2024-01-01 00:00:00', $column->getDefault());
    }

    public function testDateCreatedColumnUsesCurrentTimestampAndIsNotNull(): void
    {
        $column = new DateCreatedColumn();

        $this->assertSame('date_created', $column->getName());
        $this->assertSame(ColumnType::datetime, $column->getType());
        $this->assertFalse($column->isNull());
        $this->assertSame(\krzysztofzylka\DatabaseManager\Enum\ColumnDefault::currentTimestamp, $column->getDefault());
    }

    public function testDateModifyColumnAddsUpdateTimestampTrigger(): void
    {
        $column = new DateModifyColumn();

        $this->assertSame('date_modify', $column->getName());
        $this->assertFalse($column->isNull());
        $this->assertSame([Trigger::UpdateTimestampAfterUpdate], $column->getTriggers());
    }

    public function testEnumColumn(): void
    {
        $column = new EnumColumn('status', ['active', 'inactive'], 'active');

        $this->assertSame(ColumnType::enum, $column->getType());
        $this->assertSame("'active','inactive'", $column->getTypeSize());
        $this->assertSame('active', $column->getDefault());
    }

    public function testEmailColumn(): void
    {
        $column = new EmailColumn();

        $this->assertSame('email', $column->getName());
        $this->assertSame(ColumnType::varchar, $column->getType());
        $this->assertSame(255, $column->getTypeSize());
        $this->assertTrue($column->isNull());
    }

    public function testUsernameColumn(): void
    {
        $column = new UsernameColumn('login', 64, false);

        $this->assertSame('login', $column->getName());
        $this->assertSame(64, $column->getTypeSize());
        $this->assertFalse($column->isNull());
    }

    public function testPasswordColumn(): void
    {
        $column = new PasswordColumn();

        $this->assertSame('password', $column->getName());
        $this->assertSame(ColumnType::varchar, $column->getType());
        $this->assertSame(255, $column->getTypeSize());
    }

    public function testPhoneColumn(): void
    {
        $column = new PhoneColumn();

        $this->assertSame('phone', $column->getName());
        $this->assertSame(ColumnType::int, $column->getType());
        $this->assertSame(26, $column->getTypeSize());
    }

    public function testUuidColumnDefaultsToNotNull(): void
    {
        $column = new UuidColumn();

        $this->assertSame('uuid', $column->getName());
        $this->assertSame(ColumnType::varchar, $column->getType());
        $this->assertSame(36, $column->getTypeSize());
        $this->assertFalse($column->isNull());
    }

    public function testIdColumnOnMysqlIsUnsignedBigintPrimaryAutoincrement(): void
    {
        $this->connectWithMockPdo(DatabaseType::mysql);

        $column = new IdColumn();

        $this->assertSame('id', $column->getName());
        $this->assertSame(ColumnType::bigint, $column->getType());
        $this->assertTrue($column->isUnsigned());
        $this->assertFalse($column->isNull());
        $this->assertTrue($column->isAutoincrement());
        $this->assertTrue($column->isPrimary());
    }

    public function testIdColumnOnSqliteUsesIntegerType(): void
    {
        $this->connectWithMockPdo(DatabaseType::sqlite);

        $column = new IdColumn();

        $this->assertSame(ColumnType::integer, $column->getType());
        $this->assertTrue($column->isPrimary());
        $this->assertTrue($column->isAutoincrement());
    }
}
