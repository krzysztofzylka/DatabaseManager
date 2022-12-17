<?php

namespace DatabaseManager;

use DatabaseManager\DatabaseManager;
use DatabaseManager\Enum\ColumnType;
use DatabaseManager\Enum\DatabaseType;
use DatabaseManager\Exception\CreateTableException;
use DatabaseManager\Helper\TableColumn;
use Exception;

class CreateTable {

    private string $name;
    private array $columns = [];
    private array $primary = [];

    /**
     * Set table name
     * @param string $name
     * @return CreateTable
     */
    public function setName(string $name) : self {
        $this->name = $name;

        return $this;
    }

    /**
     * Get table name
     * @return string
     */
    private function getName() : string {
        return $this->name;
    }

    /**
     * @param TableColumn $tableColumn
     * @return CreateTable
     */
    public function addColumn(TableColumn $tableColumn) : self {
        $this->columns[] = trim(
            '`' . $tableColumn->getName() . '` '
            . strtoupper($tableColumn->getType()->name)
            . ($tableColumn->getTypeSize() ? '(' . $tableColumn->getTypeSize() . ') ' : ' ')
            . ($tableColumn->isNull() ? 'NULL ' : 'NOT NULL ')
            . ($tableColumn->isDefaultDefined()
                ? ('DEFAULT '
                    . (is_string($tableColumn->getDefault())
                        ? (str_contains($tableColumn->getDefault(), "'")
                            ? ('"' . $tableColumn->getDefault() . '"')
                            : ("'" . $tableColumn->getDefault() . "'"))
                        : $tableColumn->getDefault())
                    . ' ')
                : '')
            . (($tableColumn->isAutoincrement() && DatabaseManager::getDatabaseType() === DatabaseType::mysql) ? 'AUTO_INCREMENT ' : ' ')
            . (DatabaseManager::getDatabaseType() === DatabaseType::sqlite && $tableColumn->isPrimary() ? 'PRIMARY KEY ' : ' ')
            . ($tableColumn->getExtra())
        );

        if ($tableColumn->isPrimary() && DatabaseManager::getDatabaseType() === DatabaseType::mysql) {
            $this->primary[] = 'PRIMARY KEY (' . $tableColumn->getName() . ')';
        }

        return $this;
    }

    /**
     * Execute create table script
     * @return bool
     * @throws CreateTableException
     */
    public function execute() : bool {
        $sql = 'CREATE TABLE `' . $this->name . '` (';
        $sql .= implode(', ', $this->columns);
        $sql .= (!empty($this->primary) ? ', ' . implode(', ', $this->primary) : '');
        $sql .= ');';

        try {
            $databaseManager = new DatabaseManager();
            $databaseManager->query($sql);

            return true;
        } catch (Exception $e) {
            throw new CreateTableException($e->getMessage());
        }
    }

    /**
     * Add ID column
     * @return CreateTable
     */
    public function addIdColumn() : self {
        $tableColumn = (new TableColumn())
            ->setName('id')
            ->setType(ColumnType::int, 24)
            ->setNull(false)
            ->setAutoincrement(true)
            ->setPrimary(true);

        if (DatabaseManager::$connection->getType() === DatabaseType::sqlite) {
            $tableColumn->setType(ColumnType::integer)
                ->setAutoincrement(false);
        }

        $this->addColumn($tableColumn);

        return $this;
    }

    /**
     * Add email column
     * @param bool $null
     * @return CreateTable
     */
    public function addEmailColumn(bool $null = true) : self {
        $tableColumn = (new TableColumn())
            ->setName('email')
            ->setType(ColumnType::varchar, 255)
            ->setNull($null);

        $this->addColumn($tableColumn);

        return $this;
    }

    /**
     * Add username column
     * @param bool $null
     * @return CreateTable
     */
    public function addUsernameColumn(bool $null = true) : self {
        $tableColumn = (new TableColumn())
            ->setName('username')
            ->setType(ColumnType::varchar, 255)
            ->setNull($null);

        $this->addColumn($tableColumn);

        return $this;
    }

    /**
     * Add password column
     * @param bool $null
     * @return CreateTable
     */
    public function addPasswordColumn(bool $null = true) : self {
        $tableColumn = (new TableColumn())
            ->setName('password')
            ->setType(ColumnType::varchar, 255)
            ->setNull($null);

        $this->addColumn($tableColumn);

        return $this;
    }

    /**
     * Add phone column
     * @return CreateTable
     */
    public function addPhoneColumn() : self {
        $tableColumn = (new TableColumn())
            ->setName('phone')
            ->setType(ColumnType::int, 26);

        $this->addColumn($tableColumn);

        return $this;
    }

}