<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Helper\PrepareColumn;

class AlterTable
{

    private ?string $name = null;

    private DatabaseManager $databaseManager;

    private array $sql = [];

    /**
     * Contructor
     * @param ?string $tableName alternative for $this->setName
     */
    public function __construct(?string $tableName = null)
    {
        $this->databaseManager = new DatabaseManager();

        if (!is_null($tableName)) {
            $this->setName($tableName);
        }
    }

    /**
     * Get table name
     * @return ?string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set table name
     * @param ?string $name
     * @return AlterTable
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Add new value to enum
     * @param string $columnName column name
     * @param string $newValue new enum values
     * @param ?bool $sort sort enum values
     * @return $this
     * @throws DatabaseManagerException
     */
    public function extendEnum(string $columnName, string $newValue, ?bool $sort = true): self
    {
        $columnData = (new Table())->setName($this->name)->columnList($columnName);
        $columnType = $columnData['Type'];
        $columnType = explode("','", substr($columnType, 6, -2));
        $columnType[] = $newValue;

        if ($sort) {
            sort($columnType);
        }

        $enumValues = "'" . implode("','", $columnType) . "'";
        $this->sql[] = 'ALTER TABLE `' . $this->getName() . '` CHANGE `' . $columnName . '` `' . $columnName . '` ENUM(' . $enumValues . ');';

        return $this;
    }

    /**
     * @param Column $column
     * @param ?string $afterColumnName
     * @return AlterTable
     */
    public function addColumn(Column $column, ?string $afterColumnName = null): self
    {
        $columnString = PrepareColumn::generateCreateColumnSql($column);

        if ($column->isPrimary() && DatabaseManager::getDatabaseType() === DatabaseType::mysql) {
            $this->primary[] = 'PRIMARY KEY (' . $column->getName() . ')';
        }

        $after = '';

        if (!is_null($afterColumnName)) {
            $after .= ' AFTER `' . $afterColumnName . '`';
        }

        $this->sql[] = 'ALTER TABLE `' . $this->getName() . '` ADD ' . $columnString . $after . ';';

        return $this;
    }


    /**
     * Delete column
     * @param string $columnName
     * @return $this
     */
    public function removeColumn(string $columnName): self
    {
        $this->sql[] = 'ALTER TABLE `' . $this->getName() . '` DROP COLUMN `' . $columnName . '`;';

        return $this;
    }

    /**
     * Modify column
     * @param string $columnName
     * @param ColumnType $columnType
     * @param mixed $size
     * @return $this
     */
    public function modifyColumn(string $columnName, ColumnType $columnType, mixed $size = '255'): self
    {
        $size = $size ? '(' . $size . ') ' : '';
        $this->sql[] = 'ALTER TABLE `' . $this->getName() . '` MODIFY `' . $columnName . '` ' . $columnType->name . trim($size) . ';';

        return $this;
    }

    /**
     * Rename column name
     * @param string $oldName
     * @param string $newName
     * @return $this
     */
    public function renameColumn(string $oldName, string $newName): self
    {
        $this->sql[] = 'ALTER TABLE `' . $this->getName() . '` RENAME `' . $oldName . '` TO `' . $newName . '`;';

        return $this;
    }

    /**
     * Execute update table
     * @return void
     * @throws DatabaseManagerException
     */
    public function execute(): void
    {
        try {
            $sql = implode(PHP_EOL, $this->sql);

            if (empty($sql)) {
                return;
            }

            DatabaseManager::setLastSql($sql);
            $this->databaseManager->query($sql);
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

}