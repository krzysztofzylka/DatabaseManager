<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnDefault;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\Trigger;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    public function testCreate(): void
    {
        $column = Column::create('name', ColumnType::varchar, 100);

        $this->assertInstanceOf(Column::class, $column);
        $this->assertEquals('name', $column->getName());
        $this->assertEquals(ColumnType::varchar, $column->getType());
        $this->assertEquals(100, $column->getTypeSize());
    }

    public function testSetName(): void
    {
        $column = new Column();
        $column->setName('test_column');

        $this->assertEquals('test_column', $column->getName());
    }

    public function testSetType(): void
    {
        $column = new Column();
        $column->setType(ColumnType::int);

        $this->assertEquals(ColumnType::int, $column->getType());
        $this->assertNull($column->getTypeSize());
    }

    public function testSetTypeWithSize(): void
    {
        $column = new Column();
        $column->setType(ColumnType::varchar, 255);

        $this->assertEquals(ColumnType::varchar, $column->getType());
        $this->assertEquals(255, $column->getTypeSize());
    }

    public function testSetTypeEnum(): void
    {
        $column = new Column();
        $column->setType(ColumnType::enum, ['active', 'inactive']);

        $this->assertEquals(ColumnType::enum, $column->getType());
        $this->assertEquals("'active','inactive'", $column->getTypeSize());
    }

    public function testSetAutoincrement(): void
    {
        $column = new Column();
        $column->setAutoincrement(true);

        $this->assertTrue($column->isAutoincrement());
    }

    public function testSetPrimary(): void
    {
        $column = new Column();
        $column->setPrimary(true);

        $this->assertTrue($column->isPrimary());
    }

    public function testSetNull(): void
    {
        $column = new Column();
        $column->setNull(false);

        $this->assertFalse($column->isNull());
    }

    public function testSetDefault(): void
    {
        $column = new Column();
        $column->setDefault('default_value');

        $this->assertEquals('default_value', $column->getDefault());
        $this->assertTrue($column->isDefaultDefined());
    }

    public function testSetDefaultEnum(): void
    {
        $column = new Column();
        $column->setDefault(ColumnDefault::currentTimestamp);

        $this->assertEquals(ColumnDefault::currentTimestamp, $column->getDefault());
        $this->assertTrue($column->isDefaultDefined());
    }

    public function testSetExtra(): void
    {
        $column = new Column();
        $column->setExtra('COMMENT "Test column"');

        $this->assertEquals('COMMENT "Test column"', $column->getExtra());
    }

    public function testSetUnsigned(): void
    {
        $column = new Column();
        $column->setUnsigned(true);

        $this->assertTrue($column->isUnsigned());
    }

    public function testAddTrigger(): void
    {
        $column = new Column();
        $column->addTrigger(Trigger::UpdateTimestampAfterUpdate);

        $triggers = $column->getTriggers();
        $this->assertCount(1, $triggers);
        $this->assertEquals(Trigger::UpdateTimestampAfterUpdate, $triggers[0]);
    }

    public function testChaining(): void
    {
        $column = new Column();
        $result = $column->setName('id')
            ->setType(ColumnType::int)
            ->setUnsigned(true)
            ->setNull(false)
            ->setAutoincrement(true)
            ->setPrimary(true);

        $this->assertSame($column, $result);
        $this->assertEquals('id', $column->getName());
        $this->assertEquals(ColumnType::int, $column->getType());
        $this->assertTrue($column->isUnsigned());
        $this->assertFalse($column->isNull());
        $this->assertTrue($column->isAutoincrement());
        $this->assertTrue($column->isPrimary());
    }

    public function testConstructor(): void
    {
        $column = new Column('test', ColumnType::varchar, 100);

        $this->assertEquals('test', $column->getName());
        $this->assertEquals(ColumnType::varchar, $column->getType());
        $this->assertEquals(100, $column->getTypeSize());
    }
}