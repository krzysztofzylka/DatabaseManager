<?php

namespace DatabaseManager;

use DatabaseManager\Enum\DatabaseType;
use PDOStatement;

class DatabaseManager {

    /**
     * Database connection
     * @var DatabaseConnect
     */
    public static DatabaseConnect $connection;

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

}