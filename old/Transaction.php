<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\TransactionException;
use PDO;

class Transaction {

    /**
     * PDO Instance
     * @var PDO
     */
    private PDO $pdo;

    public function __construct() {
        $this->pdo = DatabaseManager::$connection->getConnection();
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
            $this->pdo->query($sql);
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
            $this->pdo->query($sql);
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
            $this->pdo->query($sql);
        } catch (\Exception $exception) {
            throw new TransactionException($exception->getMessage());
        }
    }

}