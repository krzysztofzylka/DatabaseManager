<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use PDO;
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

        // Keep backward compatibility
        self::$connection = $databaseConnect;

        // Add connection to ConnectionManager
        if (!is_null($connectionName)) {
            ConnectionManager::addConnection($connectionName, $databaseConnect);
        }

        // Always set the connection as default in ConnectionManager
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
     * @throws Exception
     */
    public static function getTables(?string $connectionName = null): array
    {
        $tables = [];
        $connection = is_null($connectionName)
            ? self::$connection
            : ConnectionManager::getConnection($connectionName);
        $databaseType = $connection->getType();

        switch ($databaseType->name) {
            case 'mysql':
                $query = "SHOW TABLES";
                break;
            case 'postgresql':
                $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
                break;
            case 'sqlite':
                $query = "SELECT name FROM sqlite_master WHERE type='table'";
                break;
            default:
                throw new Exception("Unsupported database type: " . $databaseType->value);
        }

        $result = $connection->getConnection()->query($query);

        if ($result !== false) {
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
        }

        return $tables;
    }

    /**
     * Get detailed information about all tables in the current database
     * @param string|null $connectionName
     * @return array Array of table details, each containing information about columns, types, and constraints.
     * @throws Exception
     */
    public static function getTableDetails(?string $connectionName = null): array
    {
        $details = [];
        $connection = is_null($connectionName)
            ? self::$connection
            : ConnectionManager::getConnection($connectionName);
        $databaseType = $connection->getType();

        switch ($databaseType->name) {
            case 'mysql':
                $query = "
                SELECT 
                    TABLE_NAME, 
                    COLUMN_NAME, 
                    COLUMN_TYPE, 
                    IS_NULLABLE, 
                    COLUMN_KEY, 
                    EXTRA 
                FROM 
                    information_schema.COLUMNS 
                WHERE 
                    TABLE_SCHEMA = DATABASE()
            ";
                break;

            case 'postgresql':
                $query = "
                SELECT 
                    c.table_name, 
                    c.column_name, 
                    c.data_type, 
                    c.is_nullable, 
                    tc.constraint_type
                FROM 
                    information_schema.columns AS c
                LEFT JOIN 
                    information_schema.key_column_usage AS kcu
                ON 
                    c.table_name = kcu.table_name AND c.column_name = kcu.column_name
                LEFT JOIN 
                    information_schema.table_constraints AS tc
                ON 
                    kcu.constraint_name = tc.constraint_name
                WHERE 
                    c.table_schema = 'public'
            ";
                break;

            case 'sqlite':
                $tableQuery = "SELECT name FROM sqlite_master WHERE type='table'";
                $tableResult = $connection->getConnection()->query($tableQuery);

                if ($tableResult !== false) {
                    while ($row = $tableResult->fetch(PDO::FETCH_NUM)) {
                        $tableName = $row[0];
                        $columnsQuery = "PRAGMA table_info('$tableName')";
                        $columnsResult = $connection->getConnection()->query($columnsQuery);

                        $tableDetails = [];
                        if ($columnsResult !== false) {
                            while ($column = $columnsResult->fetch(PDO::FETCH_ASSOC)) {
                                $tableDetails[] = [
                                    'column_name' => $column['name'],
                                    'column_type' => $column['type'],
                                    'is_nullable' => $column['notnull'] === 1 ? 'NO' : 'YES',
                                    'default_value' => $column['dflt_value'],
                                    'is_primary_key' => $column['pk'] === 1 ? 'YES' : 'NO',
                                ];
                            }
                        }
                        $details[$tableName] = $tableDetails;
                    }
                }
                return $details;

            default:
                throw new DatabaseManagerException("Unsupported database type: " . $databaseType->value);
        }

        $result = $connection->getConnection()->query($query);

        if ($result !== false) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $tableName = $row['TABLE_NAME'] ?? $row['table_name'];
                $details[$tableName][] = $row;
            }
        }

        return $details;
    }

}