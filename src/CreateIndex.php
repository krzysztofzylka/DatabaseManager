<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use PDO;

class CreateIndex
{
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
     * Columns for index
     * @var array
     */
    private array $columns;

    /**
     * PDO connection
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Connection name
     * @var string|null
     */
    private ?string $connectionName = null;

    /**
     * Constructor
     * @param string $tableName table name
     * @param string|null $connectionName Connection name
     */
    public function __construct(string $tableName, ?string $connectionName = null)
    {
        if (!is_null($connectionName)) {
            try {
                $this->connectionName = $connectionName;
                $this->pdo = ConnectionManager::getConnection($connectionName)->getConnection();
            } catch (ConnectException $e) {
                $this->pdo = DatabaseManager::$connection->getConnection();
                $this->connectionName = null;
            }
        } else {
            $this->pdo = DatabaseManager::$connection->getConnection();
        }

        $this->setTableName($tableName);
    }

    /**
     * Get connection name
     * @return string|null
     */
    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    /**
     * Set connection by name
     * @param string $connectionName
     * @return $this
     * @throws DatabaseManagerException
     */
    public function setConnection(string $connectionName): self
    {
        try {
            $connection = ConnectionManager::getConnection($connectionName);
            $this->pdo = $connection->getConnection();
            $this->connectionName = $connectionName;
        } catch (ConnectException $e) {
            throw new DatabaseManagerException("Connection '$connectionName' not found");
        }

        return $this;
    }

    /**
     * Reset to default connection
     * @return $this
     */
    public function useDefaultConnection(): self
    {
        $this->pdo = DatabaseManager::$connection->getConnection();
        $this->connectionName = null;

        return $this;
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
            DatabaseManager::setLastSql($sql);
            $this->pdo->exec($sql);

            return true;
        } catch (Exception $e) {
            throw new DatabaseManagerException($e->getMessage());
        }
    }

}