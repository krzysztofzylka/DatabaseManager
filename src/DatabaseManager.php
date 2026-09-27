<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use PDOStatement;

class DatabaseManager
{

    /**
     * Database connection - for backward compatibility
     * @var DatabaseConnect
     */
    public static DatabaseConnect $connection;

    /**
     * Latest SQL
     * @var string|null
     */
    private static ?string $lastSql = null;

    /**
     * Connect with database
     * @param DatabaseConnect $databaseConnect
     * @param string|null $connectionName Optional connection name
     * @return void
     * @throws ConnectException
     */
    public function connect(DatabaseConnect $databaseConnect, ?string $connectionName = null): void
    {
        $databaseConnect->connect();
        self::$connection = $databaseConnect;

        if (!is_null($connectionName)) {
            ConnectionManager::addConnection($connectionName, $databaseConnect);
        }

        ConnectionManager::setDefaultConnectionObject($databaseConnect);
    }

    /**
     * Query
     * @param string $query
     * @param string|null $connectionName
     * @return PDOStatement|bool
     * @throws ConnectException
     */
    public function query(string $query, ?string $connectionName = null): PDOStatement|bool
    {
        $connection = is_null($connectionName)
            ? self::$connection
            : ConnectionManager::getConnection($connectionName);

        return $connection->getConnection()->query($query);
    }

    /**
     * Get database type
     * @param string|null $connectionName
     * @return DatabaseType
     * @throws ConnectException
     */
    public static function getDatabaseType(?string $connectionName = null): DatabaseType
    {
        if (is_null($connectionName)) {
            return self::$connection->getType();
        }

        return ConnectionManager::getConnection($connectionName)->getType();
    }

    /**
     * Set last SQL
     * @param ?string $sql
     * @return void
     */
    public static function setLastSql(?string $sql): void
    {
        self::$lastSql = $sql;
    }

    /**
     * Get last SQL
     * @return ?string
     */
    public static function getLastSql(): ?string
    {
        return self::$lastSql;
    }

    /**
     * Get all tables from database
     * @param string|null $connectionName
     * @return array
     * @throws ConnectException
     */
    public static function getTables(?string $connectionName = null): array
    {
        $connection = self::resolveConnection($connectionName);

        return $connection->getType()->getDialect()->listTables($connection->getConnection());
    }

    /**
     * Get detailed information about all tables in the current database
     * @param string|null $connectionName
     * @return array Array of table details, each containing information about columns, types, and constraints.
     * @throws ConnectException
     */
    public static function getTableDetails(?string $connectionName = null): array
    {
        $connection = self::resolveConnection($connectionName);

        return $connection->getType()->getDialect()->tableDetails($connection->getConnection());
    }

    /**
     * Get the named connection, or the default one when no name is given
     * @param string|null $connectionName
     * @return DatabaseConnect
     * @throws ConnectException
     */
    private static function resolveConnection(?string $connectionName): DatabaseConnect
    {
        return is_null($connectionName)
            ? self::$connection
            : ConnectionManager::getConnection($connectionName);
    }

}
