<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Enum\Trigger;
use krzysztofzylka\DatabaseManager\Exception\DatabaseException;
use krzysztofzylka\DatabaseManager\Helper\PrepareColumn;
use krzysztofzylka\DatabaseManager\Trait\TablePredefinedColumn;
use Exception;

class CreateTable {

    use TablePredefinedColumn;

    /**
     * Table name
     * @var string
     */
    private string $name;

    /**
     * Columns
     * @var array
     */
    private array $columns = [];

    /**
     * Primary keys
     * @var array
     */
    private array $primary = [];

    /**
     * Additional sql list
     * @var array
     */
    private array $additionalSql = [];

    /**
     * Construct
     * @param ?string $name
     */
    public function __construct(?string $name = null)
    {
        if (!is_null($name)) {
            $this->setName($name);
        }
    }

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
     * @ignore
     */
    private function getName() : string {
        return $this->name;
    }

    /**
     * @param Column $column
     * @return CreateTable
     */
    public function addColumn(Column $column) : self {
        $this->columns[] = PrepareColumn::generateCreateColumnSql($column);

        if ($column->isPrimary() && DatabaseManager::getDatabaseType() === DatabaseType::mysql) {
            $this->primary[] = 'PRIMARY KEY (' . $column->getName() . ')';
        }

        if (DatabaseManager::getDatabaseType() === DatabaseType::mysql) {
            /** @var Trigger $trigger */
            foreach ($column->getTriggers() as $trigger) {
                $this->additionalSql[] = $trigger->generate($this->getName(), $column->getName()) . ';';
            }
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
        $sql .= '); ';

        try {
            $databaseManager = new DatabaseManager();
            DatabaseManager::setLastSql($sql);
            $databaseManager->query($sql);

            foreach ($this->additionalSql as $additionalSql) {
                DatabaseManager::setLastSql($additionalSql);
                $databaseManager->query($additionalSql);
            }

            return true;
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

}