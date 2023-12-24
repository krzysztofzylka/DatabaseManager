<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\TransactionException;
use PDO;

class Transaction
{

    /**
     * PDO instance
     * @var PDO
     */
    private PDO $sql;

    public function __construct()
    {
        $this->sql = DatabaseManager::$connection->getConnection();
    }

    /**
     * Begin transaction
     * @return void
     * @throws TransactionException
     */
    public function begin(): void
    {
        if (DatabaseManager::getDatabaseType() === DatabaseType::sqlite) {
            $sql = 'BEGIN TRANSACTION;';
        } else {
            $sql = 'START TRANSACTION;';
        }

        DatabaseManager::setLastSql($sql);

        try {
            $this->sql->query($sql);
        } catch (Exception $exception) {
            throw new TransactionException($exception->getMessage());
        }
    }

    /**
     * Commit transaction
     * @return void
     * @throws TransactionException
     */
    public function commit(): void
    {
        $sql = 'COMMIT;';
        DatabaseManager::setLastSql($sql);

        try {
            $this->sql->query($sql);
        } catch (Exception $exception) {
            throw new TransactionException($exception->getMessage());
        }
    }

    /**
     * Rollback transaction
     * @return void
     * @throws TransactionException
     */
    public function rollback(): void
    {
        $sql = 'ROLLBACK;';
        DatabaseManager::setLastSql($sql);

        try {
            $this->sql->query($sql);
        } catch (Exception $exception) {
            throw new TransactionException($exception->getMessage());
        }
    }

}