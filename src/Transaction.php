<?php

namespace DatabaseManager;

use DatabaseManager\Enum\DatabaseType;
use PDO;

class Transaction {

    private PDO $sql;

    public function __construct() {
        $this->sql = DatabaseManager::$connection->getConnection();
    }

    /**
     * Begin transaction
     * @return void
     */
    public function begin() : void {
        if (DatabaseManager::getDatabaseType() === DatabaseType::sqlite) {
            $sql = 'BEGIN TRANSACTION;';
        } else {
            $sql = 'START TRANSACTION;';
        }

        DatabaseManager::setLastSql($sql);
        $this->sql->query($sql);
    }

    /**
     * Commit transaction
     * @return void
     */
    public function commit() : void {
        $sql = 'COMMIT;';
        DatabaseManager::setLastSql($sql);
        $this->sql->query($sql);
    }

    /**
     * Rollback transaction
     * @return void
     */
    public function rollback() : void {
        $sql = 'ROLLBACK;';
        DatabaseManager::setLastSql($sql);
        $this->sql->query($sql);
    }

}