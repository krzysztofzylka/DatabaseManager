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

    /**
     * Regression test: execute() used to catch only Exception, so a TypeError/Error
     * (neither extends Exception) escaped without ever calling rollback(), leaving the
     * PDO transaction open. It now catches Throwable.
     */
    public function testExecuteRollsBackOnTypeErrorNotJustException(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('rollBack')->willReturn(true);

        $connect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        $transaction = new Transaction();

        try {
            $transaction->execute(function () {
                throw new \TypeError('bad type');
            });
            $this->fail('Expected TypeError was not thrown');
        } catch (\TypeError $e) {
            $this->assertSame('bad type', $e->getMessage());
        }

        $this->assertSame(0, $transaction->getCurrentLevel());
        $this->assertFalse($transaction->inTransaction());
    }

    /**
     * Regression test: commit() used to decrement the level counter BEFORE the real
     * PDO commit ran. If the real commit then failed, the counter already said "closed"
     * while the PDO transaction was still open, so execute()'s rollback attempt would
     * see level 0 and skip calling the real PDO::rollBack() entirely.
     */
    public function testExecuteRollsBackWhenCommitItselfFailsAndLevelStaysConsistent(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('commit')->willThrowException(new \PDOException('commit failed'));
        $pdo->expects($this->once())->method('rollBack')->willReturn(true);

        $connect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        $transaction = new Transaction();

        try {
            $transaction->execute(fn () => 'result');
            $this->fail('Expected TransactionException was not thrown');
        } catch (TransactionException $e) {
            // expected: real commit() failure is wrapped as TransactionException
        }

        $this->assertSame(0, $transaction->getCurrentLevel());
        $this->assertFalse($transaction->inTransaction());
    }

    /**
     * If rollback also fails after the callback failed, the original error must not be
     * silently swallowed - it stays available via getPrevious()/getHiddenMessage().
     */
    public function testExecutePreservesOriginalErrorWhenRollbackAlsoFails(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('rollBack')->willThrowException(new \PDOException('rollback failed'));

        $connect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        $transaction = new Transaction();

        try {
            $transaction->execute(function () {
                throw new \RuntimeException('original failure');
            });
            $this->fail('Expected TransactionException was not thrown');
        } catch (TransactionException $e) {
            $this->assertStringContainsString('original failure', $e->getHiddenMessage());
            $this->assertStringContainsString('rollback also failed', $e->getHiddenMessage());
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious());
            $this->assertSame('original failure', $e->getPrevious()->getMessage());
        }
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
