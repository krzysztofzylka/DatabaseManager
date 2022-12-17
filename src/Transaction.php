<?php

namespace DatabaseManager;

use DatabaseManager\Enum\DatabaseType;
use PDO;

class Transaction {

    private PDO $sql;

    public function __construct() {
        $this->sql = DatabaseManager::$connection->getConnection();
    }

    public function begin() : void {
        if (DatabaseManager::getDatabaseType() === DatabaseType::sqlite) {
            $this->sql->query('BEGIN TRANSACTION;');
        } else {
            $this->sql->query('START TRANSACTION;');
        }
    }

    public function commit() : void {
        $this->sql->query('COMMIT;');
    }

    public function rollback() : void {
        $this->sql->query('ROLLBACK;');
    }

}