<?php

namespace DatabaseManager;

use DatabaseManager\Enum\DatabaseType;
use DatabaseManager\Exception\UpdateTableException;

class AlterTable {

    private ?string $name = null;
    private DatabaseManager $databaseManager;
    private array $sql = [];

    /**
     * Połączenie z bazą danych
     */
    public function __construct() {
        $this->databaseManager = new DatabaseManager();
    }

    /**
     * Get table name
     * @return ?string
     */
    public function getName() : ?string {
        return $this->name;
    }

    /**
     * Set table name
     * @param ?string $name
     * @return AlterTable
     */
    public function setName(?string $name) : self {
        $this->name = $name;

        return $this;
    }

    /**
     * Add new value to enum
     * @param string $columnName column name
     * @param string $newValue new enum values
     * @param ?bool $sort sort enum values
     * @return $this
     */
    public function extendEnum(string $columnName, string $newValue, ?bool $sort = true) : self {
        $columnData = (new GetTable())->setName($this->name)->columnList($columnName);
        $columnType = $columnData['Type'];
        $columnType = explode("','", substr($columnType, 6, -2));
        $columnType[] = $newValue;

        if ($sort) {
            sort($columnType);
        }

        $enumValues = "'" . implode("','", $columnType) . "'";;
        $this->sql[] = 'ALTER TABLE `' . $this->getName() . '` CHANGE `' . $columnName . '` `' . $columnName . '` ENUM(' . $enumValues . ');';

        return $this;
    }

    /**
     * @param Column $column
     * @return AlterTable
     */
    public function addColumn(Column $column) : self {
        $column = trim(
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

        $this->sql[] = 'ALTER TABLE `' . $this->getName() . '` ADD ' . $column . ';';

        return $this;
    }

    /**
     * Execute update table
     * @return void
     * @throws UpdateTableException
     */
    public function execute() : void {
        try {
            var_dump($this->sql);
            foreach ($this->sql as $sql) {
                $this->databaseManager->query($sql);
            }
        } catch (\Exception $exception) {
            var_dump($exception);
            throw new UpdateTableException($exception->getMessage());
        }
    }

}