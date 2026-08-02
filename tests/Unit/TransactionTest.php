<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\ConnectionManager;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\TransactionException;
use krzysztofzylka\DatabaseManager\Transaction;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TransactionTest extends TestCase
{
    protected function setUp(): void
    {
        $reflection = new ReflectionClass(Transaction::class);

        $levels = $reflection->getProperty('transactionLevels');
        $levels->setAccessible(true);
        $levels->setValue(null, []);

        $counters = $reflection->getProperty('savepointCounters');
        $counters->setAccessible(true);
        $counters->setValue(null, []);
    }

    private function connectWithMockPdo(string $driver = 'mysql'): PDO
    {
        $pdo = $this->createStub(PDO::class);
        $pdo->method('getAttribute')->willReturn($driver);

        $connect = DatabaseConnect::create()
            ->setType(DatabaseType::mysql)
            ->setConnection($pdo);

        (new DatabaseManager())->connect($connect);

        return $pdo;
    }

    public function testBeginStartsTopLevelTransaction(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);

        $connect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        $transaction = new Transaction();
        $transaction->begin();

        $this->assertSame(1, $transaction->getCurrentLevel());
        $this->assertTrue($transaction->inTransaction());
        $this->assertSame('[begin transaction]', DatabaseManager::getLastSql());
    }

    public function testNestedBeginUsesSavepointWhenSupported(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('exec')->with('SAVEPOINT sp_1');

        $connect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        $transaction = new Transaction();
        $transaction->begin();
        $transaction->begin();

        $this->assertSame(2, $transaction->getCurrentLevel());
        $this->assertSame('[savepoint sp_1]', DatabaseManager::getLastSql());
    }

    public function testCommitWithoutActiveTransactionThrows(): void
    {
        $this->connectWithMockPdo();
        $transaction = new Transaction();

        $this->expectException(TransactionException::class);

        $transaction->commit();
    }

    public function testRollbackWithoutActiveTransactionThrows(): void
    {
        $this->connectWithMockPdo();
        $transaction = new Transaction();

        $this->expectException(TransactionException::class);

        $transaction->rollback();
    }

    public function testCommitTopLevelTransaction(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('commit')->willReturn(true);

        $connect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        $transaction = new Transaction();
        $transaction->begin();
        $transaction->commit();

        $this->assertSame(0, $transaction->getCurrentLevel());
        $this->assertFalse($transaction->inTransaction());
        $this->assertSame('[commit]', DatabaseManager::getLastSql());
    }

    public function testRollbackTopLevelTransaction(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('rollBack')->willReturn(true);

        $connect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        $transaction = new Transaction();
        $transaction->begin();
        $transaction->rollback();

        $this->assertSame(0, $transaction->getCurrentLevel());
        $this->assertSame('[rollback]', DatabaseManager::getLastSql());
    }

    public function testExecuteCommitsOnSuccessAndReturnsCallbackResult(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('commit')->willReturn(true);

        $connect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        $transaction = new Transaction();
        $result = $transaction->execute(fn () => 'callback-result');

        $this->assertSame('callback-result', $result);
        $this->assertSame(0, $transaction->getCurrentLevel());
    }

    public function testExecuteRollsBackAndRethrowsOnFailure(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('rollBack')->willReturn(true);

        $connect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        $transaction = new Transaction();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $transaction->execute(function () {
            throw new \RuntimeException('boom');
        });
    }

    public function testTransactionLevelsAreTrackedPerConnectionName(): void
    {
        $this->connectWithMockPdo();

        $defaultPdo = $this->createStub(PDO::class);
        $defaultPdo->method('getAttribute')->willReturn('mysql');
        $defaultPdo->method('beginTransaction')->willReturn(true);
        DatabaseManager::$connection = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($defaultPdo);

        $namedPdo = $this->createStub(PDO::class);
        $namedPdo->method('getAttribute')->willReturn('mysql');
        $namedPdo->method('beginTransaction')->willReturn(true);
        $namedConnect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($namedPdo);
        ConnectionManager::addConnection('secondary', $namedConnect);

        $default = new Transaction();
        $named = new Transaction('secondary');

        $default->begin();

        $this->assertSame(1, $default->getCurrentLevel());
        $this->assertSame(0, $named->getCurrentLevel());
        $this->assertSame('secondary', $named->getConnectionName());
        $this->assertNull($default->getConnectionName());
    }
}
