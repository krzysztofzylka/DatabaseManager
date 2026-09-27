<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\ConnectionManager;
use krzysztofzylka\DatabaseManager\CreateIndex;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

class CreateIndexTest extends TestCase
{
    private function connectDefaultWithMockPdo(): PDO
    {
        return $this->connectDefaultWithGivenPdo($this->createStub(PDO::class));
    }

    private function connectDefaultWithGivenPdo(PDO $pdo): PDO
    {
        $connect = DatabaseConnect::create()
            ->setType(DatabaseType::mysql)
            ->setConnection($pdo);

        (new DatabaseManager())->connect($connect);

        return $pdo;
    }

    public function testGetNameReturnsExplicitName(): void
    {
        $this->connectDefaultWithMockPdo();

        $index = CreateIndex::create('users')
            ->setName('idx_users_email')
            ->addColumn('email');

        $this->assertSame('idx_users_email', $index->getName());
    }

    public function testSetTableNameRejectsInvalidSqlIdentifier(): void
    {
        $this->connectDefaultWithMockPdo();

        $this->expectException(InvalidArgumentException::class);

        CreateIndex::create('users')->setTableName('users; DROP TABLE users; --');
    }

    public function testSetNameRejectsInvalidSqlIdentifier(): void
    {
        $this->connectDefaultWithMockPdo();

        $this->expectException(InvalidArgumentException::class);

        CreateIndex::create('users')->setName("idx' OR '1'='1");
    }

    public function testAddColumnRejectsInvalidSqlIdentifier(): void
    {
        $this->connectDefaultWithMockPdo();

        $this->expectException(InvalidArgumentException::class);

        CreateIndex::create('users')->addColumn('email; DROP TABLE users; --');
    }

    public function testGetNameFallsBackToGeneratedNameFromColumns(): void
    {
        $this->connectDefaultWithMockPdo();

        $index = CreateIndex::create('users')
            ->addColumn('email')
            ->addColumn('status');

        $this->assertSame('index_users_email_status', $index->getName());
    }

    public function testExecuteRunsExpectedSqlAndRecordsLastSql(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('exec')
            ->with('CREATE INDEX `index_users_email` ON `users`(`email`)')
            ->willReturn(0);
        $this->connectDefaultWithGivenPdo($pdo);

        $index = CreateIndex::create('users')->addColumn('email');

        $this->assertTrue($index->execute());
        $this->assertSame(
            'CREATE INDEX `index_users_email` ON `users`(`email`)',
            DatabaseManager::getLastSql()
        );
    }

    public function testExecuteWithMultipleColumns(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('exec')
            ->with('CREATE INDEX `idx_multi` ON `orders`(`user_id`, `status`)')
            ->willReturn(0);
        $this->connectDefaultWithGivenPdo($pdo);

        CreateIndex::create('orders')
            ->setName('idx_multi')
            ->addColumn('user_id')
            ->addColumn('status')
            ->execute();
    }

    public function testConstructorUsesNamedConnectionWhenProvided(): void
    {
        $this->connectDefaultWithMockPdo();

        $namedPdo = $this->createStub(PDO::class);
        $namedConnect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($namedPdo);
        ConnectionManager::addConnection('secondary', $namedConnect);

        $index = CreateIndex::create('users', 'secondary');

        $this->assertSame('secondary', $index->getConnectionName());
    }

    public function testSetConnectionThrowsForUnknownConnectionName(): void
    {
        $this->connectDefaultWithMockPdo();

        $index = CreateIndex::create('users');

        $this->expectException(DatabaseManagerException::class);

        $index->setConnection('unknown-connection');
    }

    public function testUseDefaultConnectionResetsConnectionName(): void
    {
        $this->connectDefaultWithMockPdo();

        $namedPdo = $this->createStub(PDO::class);
        $namedConnect = DatabaseConnect::create()->setType(DatabaseType::mysql)->setConnection($namedPdo);
        ConnectionManager::addConnection('secondary', $namedConnect);

        $index = CreateIndex::create('users', 'secondary')->useDefaultConnection();

        $this->assertNull($index->getConnectionName());
    }
}
