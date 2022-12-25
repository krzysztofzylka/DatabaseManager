<?php

namespace DatabaseManager\Trait;

use DatabaseManager\CreateTable;
use DatabaseManager\DatabaseManager;
use DatabaseManager\Enum\ColumnType;
use DatabaseManager\Enum\DatabaseType;
use DatabaseManager\Column;

trait TablePredefinedColumn {

    /**
     * Add ID column
     * @return CreateTable
     */
    public function addIdColumn() : CreateTable {
        $column = (new Column())
            ->setName('id')
            ->setType(ColumnType::int, 24)
            ->setNull(false)
            ->setAutoincrement(true)
            ->setPrimary(true);

        if (DatabaseManager::$connection->getType() === DatabaseType::sqlite) {
            $column->setType(ColumnType::integer)
                ->setAutoincrement(false);
        }

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add email column
     * @param bool $null is nullable
     * @param ?string $name column name
     * @return CreateTable
     */
    public function addEmailColumn(bool $null = true, ?string $name = 'email') : CreateTable {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::varchar, 255)
            ->setNull($null);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add username column
     * @param bool $null is nullable
     * @param ?string $name column name
     * @return CreateTable
     */
    public function addUsernameColumn(bool $null = true, ?string $name = 'username') : CreateTable {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::varchar, 255)
            ->setNull($null);

        $this->addColumn($column);

        return $this;
    }

    /**
     * Add password column
     * @param bool $null is nullable
     * @param ?string $name column name
     * @return CreateTable
     */
    public function addPasswordColumn(bool $null = true, ?string $name = 'password') : CreateTable {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::varchar, 255)
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
    public function addPhoneColumn(bool $null = true, ?string $name = 'phone') : CreateTable {
        $column = (new Column())
            ->setName($name)
            ->setType(ColumnType::int, 26)
            ->setNull($null);

        $this->addColumn($column);

        return $this;
    }

}