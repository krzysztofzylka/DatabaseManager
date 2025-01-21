<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\CreateTable;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\ColumnDefault;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Enum\Trigger;

trait TablePredefinedColumn
{

    /**
     * Add ID column
     * @return CreateTable
     */
    public function addIdColumn(): CreateTable
    {
        $column = (new Column())
            ->setName('id')
            ->setType(ColumnType::int)
            ->setUnsigned(true)
            ->setNull(false)
            ->setAutoincrement(true)
            ->setPrimary(true);

        if (DatabaseManager::getDatabaseType() === DatabaseType::sqlite) {
            $column = (new Column())
                ->setName('id')
                ->setType(ColumnType::integer)
                ->setPrimary(true)
                ->setAutoincrement(true);
        }

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add email column
     * @param bool $null is nullable
     * @param ?string $name column name
     * @param int $size size
     * @return CreateTable
     */
    public function addEmailColumn(bool $null = true, ?string $name = 'email', int $size = 255): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::varchar, $size)
            ->setNull($null);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add username column
     * @param bool $null is nullable
     * @param ?string $name column name
     * @param int $size
     * @return CreateTable
     */
    public function addUsernameColumn(bool $null = true, ?string $name = 'username', int $size = 255): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::varchar, $size)
            ->setNull($null);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add password column
     * @param bool $null is nullable
     * @param ?string $name column name
     * @param int $size
     * @return CreateTable
     */
    public function addPasswordColumn(bool $null = true, ?string $name = 'password', int $size = 255): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::varchar, $size)
            ->setNull($null);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add phone column
     * @param bool $null is nullable
     * @param ?string $name column name
     * @return CreateTable
     */
    public function addPhoneColumn(bool $null = true, ?string $name = 'phone'): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::int, 26)
            ->setNull($null);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add date created column
     * @param ?string $name column name
     * @return CreateTable
     */
    public function addDateCreatedColumn(?string $name = 'date_created'): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::datetime)
            ->setDefault(ColumnDefault::currentTimestamp)
            ->setNull(false);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add date modify column
     * @param ?string $name column name
     * @return CreateTable
     */
    public function addDateModifyColumn(?string $name = 'date_modify'): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::datetime)
            ->setDefault(ColumnDefault::currentTimestamp)
            ->setNull(false)
            ->addTrigger(Trigger::UpdateTimestampAfterUpdate);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add simple varchar column
     * @param string $name column name
     * @param int $size varchar length, default 255
     * @param bool $null allow null value
     * @param string|null $default
     * @return CreateTable
     */
    public function addSimpleVarcharColumn(string $name, int $size = 255, bool $null = true, ?string $default = null): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::varchar, $size)
            ->setNull($null);

        if (!is_null($default)) {
            $column->setDefault($default);
        }

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add simple int column
     * @param string $name column name
     * @param bool $null allow null value
     * @param bool $unsigned
     * @return CreateTable
     */
    public function addSimpleIntColumn(string $name, bool $null = true, bool $unsigned = false): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::int)
            ->setNull($null)
            ->setUnsigned($unsigned);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add simple bool (tinyint(1)) column
     * @param string $name column name
     * @param bool $default default
     * @return CreateTable
     */
    public function addSimpleBoolColumn(string $name, bool $default = false): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::tinyint, 1)
            ->setDefault($default ? 1 : 0);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add simple float column
     * @param string $name
     * @param string $size
     * @param float|null $default
     * @return CreateTable
     */
    public function addSimpleFloatColumn(string $name, string $size = '16,2', ?float $default = null): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::float, $size)
            ->setDefault($default);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add simple float column
     * @param string $name
     * @param string $size
     * @param float|null $default
     * @return CreateTable
     */
    public function addSimpleDecimalColumn(string $name, string $size = '16,2', ?float $default = null): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::decimal, $size)
            ->setDefault($default);

        $this->addColumn($column);

        return $this;
    }
    /**
     * Add simple text column
     * @param string $name
     * @param string|null $default
     * @return CreateTable
     */
    public function addSimpleTextColumn(string $name, ?string $default = null): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::text)
            ->setDefault($default);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add simple date column
     * @param string $name
     * @param string|null $default
     * @return CreateTable
     */
    public function addSimpleDateColumn(string $name, ?string $default = null): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::date)
            ->setDefault($default);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add simple enum column
     * @param string $name
     * @param array $values
     * @param string|null $default
     * @return CreateTable
     */
    public function addSimpleEnumColumn(string $name, array $values, ?string $default = null): CreateTable
    {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::enum, $values)
            ->setDefault($default);

        $this->addColumn($column);

        return $this;
    }

}