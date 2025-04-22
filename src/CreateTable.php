<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Enum\Trigger;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Helper\PrepareColumn;
use krzysztofzylka\DatabaseManager\Trait\TablePredefinedColumn;
use PDO;

class CreateTable
{
    use TablePredefinedColumn;

    /**
     * Table name
     * @var string
     */
    private string $name;

    /**
     * Column definitions
     * @var array
     */
    private array $columns = [];

    /**
     * Primary key definitions
     * @var array
     */
    private array $primary = [];

    /**
     * Additional SQL statements
     * @var array
     */
    private array $additionalSql = [];

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
     * Database type
     * @var DatabaseType|null
     */
    private ?DatabaseType $databaseType = null;

    /**
     * Constructor
     * @param string|null $tableName Table name
     * @param string|null $connectionName Connection name
     */
    public function __construct(?string $tableName = null, ?string $connectionName = null)
    {
        if (!is_null($connectionName)) {
            try {
                $this->connectionName = $connectionName;
                $connection = ConnectionManager::getConnection($connectionName);
                $this->pdo = $connection->getConnection();
                $this->databaseType = $connection->getType();
            } catch (ConnectException $e) {
                $this->pdo = DatabaseManager::$connection->getConnection();
                $this->connectionName = null;
                $this->databaseType = DatabaseManager::$connection->getType();
            }
        } else {
            $this->pdo = DatabaseManager::$connection->getConnection();
            $this->databaseType = DatabaseManager::$connection->getType();
        }

        if (!is_null($tableName)) {
            $this->setName($tableName);
        }
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
            $this->databaseType = $connection->getType();
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
        $this->databaseType = DatabaseManager::$connection->getType();

        return $this;
    }

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
     * @throws ConnectException
     */
    public function addColumn(Column $column): self
    {
        $this->columns[] = PrepareColumn::generateCreateColumnSql($column, $this->databaseType);

        if ($column->isPrimary() && $this->databaseType === DatabaseType::mysql) {
            $this->primary[] = 'PRIMARY KEY (' . $column->getName() . ')';
        }

        if ($this->databaseType === DatabaseType::mysql) {
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
        $sql = $this->buildCreateTableSql();

        try {
            DatabaseManager::setLastSql($sql);
            $this->pdo->exec($sql);

            foreach ($this->additionalSql as $additionalSql) {
                DatabaseManager::setLastSql($additionalSql);
                $this->pdo->exec($additionalSql);
            }

            return true;
        } catch (Exception $e) {
            throw new DatabaseManagerException($e->getMessage());
        }
    }

    /**
     * Build CREATE TABLE SQL statement based on database type
     * @return string
     */
    private function buildCreateTableSql(): string
    {
        if ($this->databaseType === DatabaseType::postgres) {
            // PostgreSQL używa podwójnych cudzysłowów dla identyfikatorów
            $sql = 'CREATE TABLE "' . $this->name . '" (';
            $sql .= implode(', ', $this->columns);
            // W PostgreSQL, klucze primary są zwykle definiowane w definicji kolumny
            $sql .= ')';

            return $sql;
        } else {
            // MySQL/SQLite używa backticks
            $sql = 'CREATE TABLE `' . $this->name . '` (';
            $sql .= implode(', ', $this->columns);
            $sql .= (!empty($this->primary) ? ', ' . implode(', ', $this->primary) : '');
            $sql .= ')';

            return $sql;
        }
    }
}