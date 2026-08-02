<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseConnectTest extends TestCase
{
    public function testDefaults(): void
    {
        $connect = DatabaseConnect::create();

        $this->assertSame('127.0.0.1', $connect->getHost());
        $this->assertSame(3306, $connect->getPort());
        $this->assertSame('utf8', $connect->getCharset());
        $this->assertSame('database.sqlite', $connect->getSqlitePath());
        $this->assertSame(DatabaseType::mysql, $connect->getType());
        $this->assertSame('', $connect->getPassword());
    }

    public function testSettersAreFluentAndTrimValues(): void
    {
        $connect = DatabaseConnect::create()
            ->setHost(' 10.0.0.1 ')
            ->setDatabaseName(' app_db ')
            ->setUsername(' root ')
            ->setPassword(' secret ')
            ->setPort(5432)
            ->setCharset(' utf8mb4 ')
            ->setSqlitePath(' /tmp/db.sqlite ')
            ->setType(DatabaseType::postgres);

        $this->assertSame('10.0.0.1', $connect->getHost());
        $this->assertSame('app_db', $connect->getDatabaseName());
        $this->assertSame('root', $connect->getUsername());
        $this->assertSame('secret', $connect->getPassword());
        $this->assertSame(5432, $connect->getPort());
        $this->assertSame('utf8mb4', $connect->getCharset());
        $this->assertSame('/tmp/db.sqlite', $connect->getSqlitePath());
        $this->assertSame(DatabaseType::postgres, $connect->getType());
    }

    public function testSetConnectionStoresPdoAndMarksManualConnection(): void
    {
        $pdo = $this->createStub(PDO::class);
        $connect = DatabaseConnect::create()->setConnection($pdo);

        $this->assertSame($pdo, $connect->getConnection());
    }

    public function testConnectIsNoOpWhenConnectionWasSetManually(): void
    {
        $pdo = $this->createStub(PDO::class);
        $connect = DatabaseConnect::create()->setConnection($pdo);

        $this->assertFalse($connect->connect());
        $this->assertSame($pdo, $connect->getConnection());
    }

    public function testGetConnectionIdReadsFromPdo(): void
    {
        $statement = $this->createStub(\PDOStatement::class);
        $statement->method('fetch')->willReturn(['conn_id' => 42]);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('query')->willReturn($statement);

        $connect = DatabaseConnect::create()->setConnection($pdo);

        $this->assertSame(42, $connect->getConnectionId());
    }
}
