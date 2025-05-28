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
     * Transaction level counter
     * @var int
     */
    private static array $transactionLevels = [];

    /**
     * Savepoint counter for unique names
     * @var int
     */
    private static array $savepointCounters = [];

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
     * Get connection identifier for tracking transaction levels
     * @return string
     */
    private function getConnectionId(): string
    {
        return $this->connectionName ?? 'default';
    }

    /**
     * Get current transaction level
     * @return int
     */
    private function getTransactionLevel(): int
    {
        $connId = $this->getConnectionId();
        return self::$transactionLevels[$connId] ?? 0;
    }

    /**
     * Set transaction level
     * @param int $level
     * @return void
     */
    private function setTransactionLevel(int $level): void
    {
        $connId = $this->getConnectionId();
        self::$transactionLevels[$connId] = $level;
    }

    /**
     * Get next savepoint counter
     * @return int
     */
    private function getNextSavepointCounter(): int
    {
        $connId = $this->getConnectionId();
        if (!isset(self::$savepointCounters[$connId])) {
            self::$savepointCounters[$connId] = 0;
        }
        return ++self::$savepointCounters[$connId];
    }

    /**
     * Check if database supports savepoints
     * @return bool
     */
    private function supportsSavepoints(): bool
    {
        $driver = $this->sql->getAttribute(PDO::ATTR_DRIVER_NAME);
        return in_array($driver, ['mysql', 'pgsql', 'sqlite']);
    }

    /**
     * Begin transaction or create savepoint
     * @return void
     * @throws TransactionException
     */
    public function begin(): void
    {
        try {
            $level = $this->getTransactionLevel();

            if ($level === 0) {
                $this->sql->beginTransaction();
                DatabaseManager::setLastSql('[begin transaction]');
            } elseif ($this->supportsSavepoints()) {
                $savepointName = 'sp_' . $this->getNextSavepointCounter();
                $this->sql->exec("SAVEPOINT $savepointName");
                DatabaseManager::setLastSql("[savepoint $savepointName]");
            } else {
                DatabaseManager::setLastSql('[nested transaction - ignored]');
            }

            $this->setTransactionLevel($level + 1);
        } catch (Exception $exception) {
            throw new TransactionException($exception->getMessage());
        }
    }

    /**
     * Commit transaction or release savepoint
     * @return void
     * @throws TransactionException
     */
    public function commit(): void
    {
        try {
            $level = $this->getTransactionLevel();

            if ($level === 0) {
                throw new TransactionException('No active transaction to commit');
            }

            $this->setTransactionLevel($level - 1);

            if ($level === 1) {
                $this->sql->commit();
                DatabaseManager::setLastSql('[commit]');
            } elseif ($this->supportsSavepoints()) {
                $savepointName = 'sp_' . self::$savepointCounters[$this->getConnectionId()];
                $this->sql->exec("RELEASE SAVEPOINT $savepointName");
                DatabaseManager::setLastSql("[release savepoint $savepointName]");
                self::$savepointCounters[$this->getConnectionId()]--;
            } else {
                DatabaseManager::setLastSql('[nested commit - ignored]');
            }
        } catch (Exception $exception) {
            throw new TransactionException($exception->getMessage());
        }
    }

    /**
     * Rollback transaction or to savepoint
     * @return void
     * @throws TransactionException
     */
    public function rollback(): void
    {
        try {
            $level = $this->getTransactionLevel();

            if ($level === 0) {
                throw new TransactionException('No active transaction to rollback');
            }

            if ($level === 1) {
                $this->sql->rollBack();
                DatabaseManager::setLastSql('[rollback]');
            } elseif ($this->supportsSavepoints()) {
                $savepointName = 'sp_' . self::$savepointCounters[$this->getConnectionId()];
                $this->sql->exec("ROLLBACK TO SAVEPOINT $savepointName");
                DatabaseManager::setLastSql("[rollback to savepoint $savepointName]");
                self::$savepointCounters[$this->getConnectionId()]--;
            } else {
                $this->sql->rollBack();
                DatabaseManager::setLastSql('[rollback entire transaction]');
            }

            if ($level === 1 || !$this->supportsSavepoints()) {
                $this->setTransactionLevel(0);
            } else {
                $this->setTransactionLevel($level - 1);
            }
        } catch (Exception $exception) {
            throw new TransactionException($exception->getMessage());
        }
    }

    /**
     * Check if currently in transaction
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getTransactionLevel() > 0;
    }

    /**
     * Get current transaction level
     * @return int
     */
    public function getCurrentLevel(): int
    {
        return $this->getTransactionLevel();
    }

    /**
     * Get connection name
     * @return string|null
     */
    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    /**
     * Execute callback within transaction
     * @param callable $callback
     * @return mixed
     * @throws TransactionException
     */
    public function execute(callable $callback)
    {
        $this->begin();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}