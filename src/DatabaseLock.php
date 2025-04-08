<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
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
    private ?string $serverIdentifier;

    /**
     * Constructor
     * @throws DatabaseManagerException
     * @throws Exception
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

        if (!$this->table->isColumnUnique('lock_name')) {
            $this->makeColumnUnique('lock_name', 'database_locks');
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

        if ($this->lockExists($name)) {
            return false;
        }

        try {
            $this->table->insert([
                'lock_name' => $name,
                'lock_time' => date('Y-m-d H:i:s'),
                'lock_expiration' => date('Y-m-d H:i:s', strtotime("+$timeout seconds")),
                'server_identifier' => $this->serverIdentifier
            ]);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Unlock
     * @param string $name
     * @return bool
     * @throws DatabaseManagerException
     */
    public function unlock(string $name): bool {
        return $this->table->deleteByConditions(['lock_name' => $name, 'server_identifier' => $this->serverIdentifier]);
    }

    /**
     * Lock exists
     * @param string $name
     * @return bool
     * @throws DatabaseManagerException
     */
    public function lockExists(string $name): bool {
        return $this->table->findIsset(['database_locks.lock_name' => $name]);
    }

    /**
     * Delete expired locks
     * @return void
     * @throws DatabaseManagerException
     */
    private function cleanExpiredLocks(): void {
        $this->table->deleteByConditions([new Condition('lock_expiration', '<', date('Y-m-d H:i:s'))]);
    }

    /**
     * Set unique column
     * @param string $columnName
     * @param string $tableName
     * @return void
     */
    private function makeColumnUnique(string $columnName, string $tableName): void
    {
        $query = "ALTER TABLE {$tableName} ADD UNIQUE ({$columnName})";
        $this->table->query($query);
    }

}