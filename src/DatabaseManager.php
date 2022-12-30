<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use PDOStatement;

class DatabaseManager {

    /**
     * Database connection
     * @var DatabaseConnect
     */
    public static DatabaseConnect $connection;

    private static ?string $lastSql = null;

    /**
     * Connect with database
     * @param DatabaseConnect $databaseConnect
     * @return void
     * @throws Exception\ConnectException
     */
    public function connect(DatabaseConnect $databaseConnect) : void {
        $databaseConnect->connect();
        self::$connection = $databaseConnect;
    }

    /**
     * Query
     * @param string $query
     * @return PDOStatement|bool
     */
    public function query(string $query) : PDOStatement|bool {
        return self::$connection->getConnection()->query($query);
    }

    /**
     * Get database type
     * @return DatabaseType
     */
    public static function getDatabaseType() : DatabaseType {
        return self::$connection->getType();
    }

    /**
     * Set last SQL
     * @param ?string $sql
     * @return void
     */
    public static function setLastSql(?string $sql) : void {
        self::$lastSql = $sql;

        if (self::$connection->isDebug()) {
            Debug::addSql($sql);
        }
    }

    /**
     * Get last SQL
     * @return ?string
     */
    public static function getLastSql() : ?string {
        return self::$lastSql;
    }

}