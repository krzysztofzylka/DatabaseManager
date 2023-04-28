<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Exception\DatabaseException;
use Exception;
use krzysztofzylka\DatabaseManager\Helper\SqlBuilder;

class CreateIndex {

    /**
     * Table name
     * @var string
     */
    private string $tableName;

    /**
     * Index name
     * @var string
     */
    private string $name;

    /**
     * Columns list
     * @var array
     */
    private array $columns;

    /**
     * Constructor
     * @param ?string $tableName table name
     */
    public function __construct(?string $tableName = null) {
        $this->setTableName($tableName);
    }

    /**
     * Set table name
     * @param string $tableName table name
     * @return CreateIndex
     */
    public function setTableName(string $tableName) : CreateIndex {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * Define index name
     * @param string $name
     * @return CreateIndex
     */
    public function setName(string $name) : CreateIndex {
        $this->name = $name;

        return $this;
    }

    /**
     * Add columns
     * @param string $name
     * @return $this
     */
    public function addColumn(string $name) : CreateIndex {
        $this->columns[] = $name;

        return $this;
    }

    /**
     * Execute sql
     * @return bool
     * @throws DatabaseException
     */
    public function execute() : bool {
        try {
            $sql = SqlBuilder::createIndex($this->name, $this->tableName, $this->columns);
            $databaseManager = new DatabaseManager();
            DatabaseManager::setLastSql($sql);
            $databaseManager->query($sql);
            return true;
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

}