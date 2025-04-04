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
        try {
            $this->sql->beginTransaction();
            DatabaseManager::setLastSql('[begin transaction]');
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
        try {
            $this->sql->commit();
            DatabaseManager::setLastSql('[commit]');
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
        try {
            $this->sql->rollBack();
            DatabaseManager::setLastSql('[rollback]');
        } catch (Exception $exception) {
            throw new TransactionException($exception->getMessage());
        }

    }

}