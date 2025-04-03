<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\ColumnDefault;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;

class DatabaseLock
{

    /**
     * Table instance
     * @var Table
     */
    private Table $table;

    /**
     * Default timeout in seconds
     * @var int
     */
    private int $defaultTimeout = 120;

    /**
     * Server identifier
     * @var ?string
     */
    private ?string $serverIdentifier = null;

    /**
     * Constructor
     * @throws DatabaseManagerException
     */
    public function __construct() {
        $this->serverIdentifier = gethostname();
        $this->table = new Table('database_locks');

        if (!$this->table->exists()) {
            $createTable = new CreateTable();
            $createTable->setName('database_locks');
            $createTable->addSimpleVarcharColumn('lock_name', 64);
            $createTable->addColumn(
                (new Column('lock_time'))
                    ->setType(ColumnType::timestamp)
                    ->setNull(false)
                    ->setDefault(ColumnDefault::currentTimestamp)
            );
            $createTable->addColumn(
                (new Column('lock_expiration'))
                    ->setType(ColumnType::timestamp)
                    ->setNull(true)
            );
            $createTable->addSimpleVarcharColumn('server_identifier');
            $createTable->execute();
        }
    }

    /**
     * Lock
     * @param string $name
     * @param int|null $timeout
     * @return bool
     * @throws DatabaseManagerException
     */
    public function lock(string $name, int $timeout = null): bool
    {
        $timeout = $timeout ?? $this->defaultTimeout;
        $this->cleanExpiredLocks();

        if ($this->table->findIsset(['database_locks.lock_name' => $name])) {
            return false;
        }

        $stmt = $this->table->getPdoInstance()->prepare("
            INSERT INTO database_locks (lock_name, lock_time, lock_expiration, server_identifier) 
            VALUES (:name, NOW(), DATE_ADD(NOW(), INTERVAL :timeout SECOND), :server)
            ON DUPLICATE KEY UPDATE
                lock_name = IF(lock_expiration < NOW(), VALUES(lock_name), lock_name),
                lock_time = IF(lock_expiration < NOW(), VALUES(lock_time), lock_time),
                lock_expiration = IF(lock_expiration < NOW(), VALUES(lock_expiration), lock_expiration),
                server_identifier = IF(lock_expiration < NOW(), VALUES(server_identifier), server_identifier)
        ");
        $stmt->execute([':name' => $name, ':timeout' => $timeout, ':server' => $this->serverIdentifier]);
        $stmt = $this->table->getPdoInstance()->prepare("SELECT COUNT(*) FROM database_locks WHERE lock_name = :name AND server_identifier = :server");
        $stmt->execute([':name' => $name, ':server' => $this->serverIdentifier]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Unlock
     * @param string $name
     * @return bool
     */
    public function unlock(string $name): bool {
        $stmt = $this->table->getPdoInstance()->prepare("DELETE FROM database_locks WHERE lock_name = :name AND server_identifier = :server");
        $stmt->execute([':name' => $name, ':server' => $this->serverIdentifier]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete expired locks
     */
    private function cleanExpiredLocks(): void {
        $this->table->getPdoInstance()->exec("DELETE FROM database_locks WHERE lock_expiration < NOW()");
    }

}