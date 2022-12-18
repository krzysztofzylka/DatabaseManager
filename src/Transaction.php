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
            $this->sql->query('BEGIN TRANSACTION;');
        } else {
            $this->sql->query('START TRANSACTION;');
        }
    }

    /**
     * Commit transaction
     * @return void
     */
    public function commit() : void {
        $this->sql->query('COMMIT;');
    }

    /**
     * Rollback transaction
     * @return void
     */
    public function rollback() : void {
        $this->sql->query('ROLLBACK;');
    }

}