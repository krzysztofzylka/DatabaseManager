<?php

namespace DatabaseManager;

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

//        var_dump(self::$connection->getConnection());
    }

}