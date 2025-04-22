<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use krzysztofzylka\DatabaseManager\Exception\TransactionException;
use PDO;

class Transaction
{

    /**
     * PDO instance
     * @var PDO
     */
    private PDO $sql;

    /**
     * Connection name
     * @var string|null
     */
    private ?string $connectionName;

    /**
     * Constructor
     * @param string|null $connectionName
     * @throws ConnectException
     */
    public function __construct(?string $connectionName = null)
    {
        $this->connectionName = $connectionName;

        if (!is_null($connectionName)) {
            $this->sql = ConnectionManager::getConnection($connectionName)->getConnection();
        } else {
            $this->sql = DatabaseManager::$connection->getConnection();
        }
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

    /**
     * Get connection name
     * @return string|null
     */
    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

}