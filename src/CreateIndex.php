<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Helper\SqlIdentifier;
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
     * Database type
     * @var DatabaseType
     */
    private DatabaseType $databaseType;

    /**
     * Connection name
     * @var string|null
     */
    private ?string $connectionName = null;

    /**
     * Static create method
     * @param string $tableName
     * @param string|null $connectionName
     * @return CreateIndex
     */
    public static function create(string $tableName, ?string $connectionName = null): CreateIndex
    {
        return new CreateIndex($tableName, $connectionName);
    }

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
                $connection = ConnectionManager::getConnection($connectionName);
                $this->pdo = $connection->getConnection();
                $this->databaseType = $connection->getType();
            } catch (ConnectException $e) {
                $this->pdo = DatabaseManager::$connection->getConnection();
                $this->databaseType = DatabaseManager::$connection->getType();
                $this->connectionName = null;
            }
        } else {
            $this->pdo = DatabaseManager::$connection->getConnection();
            $this->databaseType = DatabaseManager::$connection->getType();
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
            $this->databaseType = $connection->getType();
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
        $this->databaseType = DatabaseManager::$connection->getType();
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
        $this->tableName = SqlIdentifier::validate($tableName);

        return $this;
    }

    /**
     * Define index name
     * @param string $name
     * @return CreateIndex
     */
    public function setName(string $name): CreateIndex
    {
        $this->name = SqlIdentifier::validate($name);

        return $this;
    }

    /**
     * Add columns
     * @param string $name
     * @return $this
     */
    public function addColumn(string $name): CreateIndex
    {
        $this->columns[] = SqlIdentifier::validate($name);

        return $this;
    }

    /**
     * Get name
     * @return string
     */
    public function getName(): string
    {
        if (!isset($this->name)) {
            return 'index_' . $this->tableName . '_' . implode('_', $this->columns);
        }

        return $this->name;
    }

    /**
     * Execute sql
     * @return bool
     * @throws DatabaseManagerException
     */
    public function execute(): bool
    {
        $dialect = $this->databaseType->getDialect();
        $sql = 'CREATE INDEX ' . $dialect->quoteIdentifier($this->getName())
            . ' ON ' . $dialect->quoteIdentifier($this->tableName)
            . '(' . implode(', ', array_map([$dialect, 'quoteIdentifier'], $this->columns)) . ')';

        try {
            DatabaseManager::setLastSql($sql);
            $this->pdo->exec($sql);

            return true;
        } catch (Exception $e) {
            throw new DatabaseManagerException($e->getMessage());
        }
    }

}