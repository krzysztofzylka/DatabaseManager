<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseLock;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class DatabaseLockTest extends TestCase
{
    /**
     * Builds a DatabaseLock backed by a PDO stub that:
     *  - reports the `database_locks` table as already existing (skips CreateTable)
     *  - reports `lock_name` as already unique (skips the ALTER TABLE ADD UNIQUE step)
     *  - answers findCount()/findIsset() (used by lockExists) with $lockCount
     */
    private function createLock(int $lockCount = 0): DatabaseLock
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchColumn')->willReturn(1); // Table::exists() -> table found
        $statement->method('fetch')->willReturn(['count' => $lockCount]); // findCount()

        $tableDetailsStatement = $this->createStub(PDOStatement::class);
        $tableDetailsStatement->method('fetch')->willReturnOnConsecutiveCalls(
            ['TABLE_NAME' => 'database_locks', 'COLUMN_NAME' => 'lock_name', 'COLUMN_KEY' => 'UNI'],
            false
        );

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);
        $pdo->method('query')->willReturn($tableDetailsStatement);
        $pdo->method('lastInsertId')->willReturn('1');

        $connect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        return new DatabaseLock();
    }

    public function testLockSucceedsWhenNoExistingLock(): void
    {
        $lock = $this->createLock(lockCount: 0);

        $this->assertTrue($lock->lock('my-lock'));
    }

    public function testLockFailsWhenAlreadyLocked(): void
    {
        $lock = $this->createLock(lockCount: 1);

        $this->assertFalse($lock->lock('my-lock'));
    }

    public function testUnlockReturnsTrueOnSuccess(): void
    {
        $lock = $this->createLock();

        $this->assertTrue($lock->unlock('my-lock'));
    }

    public function testLockExistsReflectsFindCountResult(): void
    {
        $locked = $this->createLock(lockCount: 1);
        $free = $this->createLock(lockCount: 0);

        $this->assertTrue($locked->lockExists('my-lock'));
        $this->assertFalse($free->lockExists('other-lock'));
    }

    public function testGetConnectionNameDefaultsToNull(): void
    {
        $lock = $this->createLock();

        $this->assertNull($lock->getConnectionName());
    }

    public function testUseDefaultConnectionResetsConnectionName(): void
    {
        $lock = $this->createLock();
        $lock->useDefaultConnection();

        $this->assertNull($lock->getConnectionName());
    }
}
