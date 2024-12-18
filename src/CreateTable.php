<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Enum\Trigger;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Helper\PrepareColumn;
use krzysztofzylka\DatabaseManager\Trait\TablePredefinedColumn;

class CreateTable
{

    use TablePredefinedColumn;

    private string $name;

    private array $columns = [];

    private array $primary = [];

    private array $additionalSql = [];

    /**
     * Set table name
     * @param string $name
     * @return CreateTable
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get table name
     * @return string
     * @ignore
     */
    private function getName(): string
    {
        return $this->name;
    }

    /**
     * @param Column $column
     * @return CreateTable
     */
    public function addColumn(Column $column): self
    {
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
     * @throws DatabaseManagerException
     */
    public function execute(): bool
    {
        $sql = 'CREATE TABLE `' . $this->name . '` (';
        $sql .= implode(', ', $this->columns);
        $sql .= (!empty($this->primary) ? ', ' . implode(', ', $this->primary) : '');
        $sql .= ');';

        try {
            $databaseManager = new DatabaseManager();
            DatabaseManager::setLastSql($sql);
            $databaseManager->query($sql);

            foreach ($this->additionalSql as $additionalSql) {
                DatabaseManager::setLastSql($additionalSql);
                $databaseManager->query($additionalSql);
            }

            return true;
        } catch (Exception $e) {
            throw new DatabaseManagerException($e->getMessage());
        }
    }

}