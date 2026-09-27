<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Dialect\DatabaseDialect;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Helper\SqlIdentifier;

class AlterTable
{

    private ?string $name = null;

    private DatabaseManager $databaseManager;

    private array $sql = [];

    /**
     * Static create method
     * @param string $tableName
     * @return self
     */
    public static function create(string $tableName): self
    {
        return new self($tableName);
    }

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
        if (!is_null($name)) {
            SqlIdentifier::validate($name);
        }

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
        SqlIdentifier::validate($columnName);

        $columnData = (new Table())->setName($this->name)->columnList($columnName);
        $columnType = $columnData['Type'];
        $columnType = explode("','", substr($columnType, 6, -2));
        $columnType[] = $newValue;

        if ($sort) {
            sort($columnType);
        }

        $enumValues = "'" . implode("','", $columnType) . "'";
        $column = $this->quote($columnName);
        $this->sql[] = 'ALTER TABLE ' . $this->quote($this->getName()) . ' CHANGE ' . $column . ' ' . $column . ' ENUM(' . $enumValues . ');';

        return $this;
    }

    /**
     * @param Column $column
     * @param ?string $afterColumnName
     * @return AlterTable
     * @throws ConnectException
     */
    public function addColumn(Column $column, ?string $afterColumnName = null): self
    {
        $columnString = $this->getDialect()->columnDefinition($column);
        $after = '';

        if (!is_null($afterColumnName)) {
            SqlIdentifier::validate($afterColumnName);
            $after .= ' AFTER ' . $this->quote($afterColumnName);
        }

        $this->sql[] = 'ALTER TABLE ' . $this->quote($this->getName()) . ' ADD ' . $columnString . $after . ';';

        return $this;
    }


    /**
     * Delete column
     * @param string $columnName
     * @return $this
     */
    public function removeColumn(string $columnName): self
    {
        SqlIdentifier::validate($columnName);

        $this->sql[] = 'ALTER TABLE ' . $this->quote($this->getName()) . ' DROP COLUMN ' . $this->quote($columnName) . ';';

        return $this;
    }

    /**
     * Modify column
     * @param string $columnName
     * @param ColumnType $columnType
     * @param mixed $size
     * @param array $attributes addional atributes (np. ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'])
     * @return $this
     */
    public function modifyColumn(string $columnName, ColumnType $columnType, mixed $size = '255', array $attributes = []): self
    {
        SqlIdentifier::validate($columnName);

        $size = $size ? '(' . $size . ') ' : '';
        $attributesString = !empty($attributes) ? ' ' . implode(' ', $attributes) : '';

        $this->sql[] = 'ALTER TABLE ' . $this->quote($this->getName()) . ' MODIFY ' . $this->quote($columnName) . ' ' . $columnType->name . trim($size) . $attributesString . ';';

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
        SqlIdentifier::validate($oldName);
        SqlIdentifier::validate($newName);

        $this->sql[] = 'ALTER TABLE ' . $this->quote($this->getName()) . ' RENAME COLUMN ' . $this->quote($oldName) . ' TO ' . $this->quote($newName) . ';';

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

            // Clear cache for this table after modification
            Cache::clearTableCache($this->getName());
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

    /**
     * SQL dialect of the default connection
     * @return DatabaseDialect
     * @throws ConnectException
     */
    private function getDialect(): DatabaseDialect
    {
        return DatabaseManager::getDatabaseType()->getDialect();
    }

    /**
     * Quote an identifier for the default connection
     * @param string $name
     * @return string
     * @throws ConnectException
     */
    private function quote(string $name): string
    {
        return $this->getDialect()->quoteIdentifier($name);
    }

}
