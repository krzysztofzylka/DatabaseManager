<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use Exception;

class CreateIndex
{

    private string $tableName;

    private string $name;

    private array $columns;

    /**
     * Constructor
     * @param string $tableName table name
     */
    public function __construct(string $tableName)
    {
        $this->setTableName($tableName);
    }

    /**
     * Set table name
     * @param string $tableName table name
     * @return CreateIndex
     */
    public function setTableName(string $tableName): CreateIndex
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * Define index name
     * @param string $name
     * @return CreateIndex
     */
    public function setName(string $name): CreateIndex
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Add columns
     * @param string $name
     * @return $this
     */
    public function addColumn(string $name): CreateIndex
    {
        $this->columns[] = $name;

        return $this;
    }

    /**
     * Execute sql
     * @return bool
     * @throws DatabaseManagerException
     */
    public function execute(): bool
    {
        $sql = 'CREATE INDEX ' . $this->name . ' ON ' . $this->tableName . '(' . implode(',', $this->columns) . ')';

        try {
            $databaseManager = new DatabaseManager();
            DatabaseManager::setLastSql($sql);
            $databaseManager->query($sql);
            return true;
        } catch (Exception $e) {
            throw new DatabaseManagerException($e->getMessage());
        }
    }

}