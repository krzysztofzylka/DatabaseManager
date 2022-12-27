<?php

namespace DatabaseManager;

use DatabaseManager\Enum\DatabaseType;
use DatabaseManager\Exception\UpdateTableException;
use DatabaseManager\Helper\PrepareColumn;
use Exception;

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
        $columnData = (new Table())->setName($this->name)->columnList($columnName);
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
        $columnString = PrepareColumn::generateCreateColumnSql($column);

        if ($column->isPrimary() && DatabaseManager::getDatabaseType() === DatabaseType::mysql) {
            $this->primary[] = 'PRIMARY KEY (' . $column->getName() . ')';
        }

        $this->sql[] = 'ALTER TABLE `' . $this->getName() . '` ADD ' . $columnString . ';';

        return $this;
    }

    /**
     * Execute update table
     * @return void
     * @throws UpdateTableException
     */
    public function execute() : void {
        try {
            $sql = implode(PHP_EOL, $this->sql);
            DatabaseManager::setLastSql($sql);
            $this->databaseManager->query($sql);
        } catch (Exception $exception) {
            throw new UpdateTableException($exception->getMessage());
        }
    }

}