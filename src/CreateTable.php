<?php

namespace DatabaseManager;

use DatabaseManager\Enum\DatabaseType;
use DatabaseManager\Exception\CreateTableException;
use DatabaseManager\Trait\TablePredefinedColumn;
use Exception;

class CreateTable {

    use TablePredefinedColumn;

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
     * @param Column $column
     * @return CreateTable
     */
    public function addColumn(Column $column) : self {
        $this->columns[] = trim(
            '`' . $column->getName() . '` '
            . strtoupper($column->getType()->name)
            . ($column->getTypeSize() ? '(' . $column->getTypeSize() . ') ' : ' ')
            . ($column->isNull() ? 'NULL ' : 'NOT NULL ')
            . ($column->isDefaultDefined()
                ? ('DEFAULT '
                    . (is_string($column->getDefault())
                        ? (str_contains($column->getDefault(), "'")
                            ? ('"' . $column->getDefault() . '"')
                            : ("'" . $column->getDefault() . "'"))
                        : $column->getDefault())
                    . ' ')
                : '')
            . (($column->isAutoincrement() && DatabaseManager::getDatabaseType() === DatabaseType::mysql) ? 'AUTO_INCREMENT ' : ' ')
            . (DatabaseManager::getDatabaseType() === DatabaseType::sqlite && $column->isPrimary() ? 'PRIMARY KEY ' : ' ')
            . ($column->getExtra())
        );

        if ($column->isPrimary() && DatabaseManager::getDatabaseType() === DatabaseType::mysql) {
            $this->primary[] = 'PRIMARY KEY (' . $column->getName() . ')';
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

}