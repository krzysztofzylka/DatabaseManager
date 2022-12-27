<?php

namespace DatabaseManager;

use DatabaseManager\Enum\DatabaseType;
use DatabaseManager\Exception\TransactionException;
use PDO;

class Transaction {

    private PDO $sql;

    public function __construct() {
        $this->sql = DatabaseManager::$connection->getConnection();
    }

    /**
     * Begin transaction
     * @return void
     * @throws TransactionException
     */
    public function begin() : void {
        if (DatabaseManager::getDatabaseType() === DatabaseType::sqlite) {
            $sql = 'BEGIN TRANSACTION;';
        } else {
            $sql = 'START TRANSACTION;';
        }

        DatabaseManager::setLastSql($sql);

        try {
            $this->sql->query($sql);
        } catch (\Exception $exception) {
            throw new TransactionException($exception->getMessage());
        }
    }

    /**
     * Commit transaction
     * @return void
     * @throws TransactionException
     */
    public function commit() : void {
        $sql = 'COMMIT;';
        DatabaseManager::setLastSql($sql);

        try {
            $this->sql->query($sql);
        } catch (\Exception $exception) {
            throw new TransactionException($exception->getMessage());
        }
    }

    /**
     * Rollback transaction
     * @return void
     * @throws TransactionException
     */
    public function rollback() : void {
        $sql = 'ROLLBACK;';
        DatabaseManager::setLastSql($sql);

        try {
            $this->sql->query($sql);
        } catch (\Exception $exception) {
            throw new TransactionException($exception->getMessage());
        }
    }

}