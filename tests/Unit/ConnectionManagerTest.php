<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\ConnectionManager;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ConnectionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetConnectionManager();
    }

    protected function tearDown(): void
    {
        $this->resetConnectionManager();
    }

    private function resetConnectionManager(): void
    {
        $reflection = new ReflectionClass(ConnectionManager::class);

        $connections = $reflection->getProperty('connections');
        $connections->setAccessible(true);
        $connections->setValue(null, []);

        $default = $reflection->getProperty('defaultConnection');
        $default->setAccessible(true);
        $default->setValue(null, null);
    }

    private function makeConnection(): DatabaseConnect
    {
        return DatabaseConnect::create()->setType(DatabaseType::mysql);
    }

    public function testGetConnectionWithoutDefaultThrows(): void
    {
        $this->expectException(ConnectException::class);

        ConnectionManager::getConnection();
    }

    public function testGetConnectionForUnknownNameThrows(): void
    {
        $this->expectException(ConnectException::class);

        ConnectionManager::getConnection('missing');
    }

    public function testAddConnectionFirstOneBecomesDefault(): void
    {
        $connection = $this->makeConnection();
        ConnectionManager::addConnection('primary', $connection);

        $this->assertSame($connection, ConnectionManager::getConnection('primary'));
        $this->assertSame($connection, ConnectionManager::getConnection());
    }

    public function testAddConnectionDoesNotOverrideDefaultUnlessRequested(): void
    {
        $first = $this->makeConnection();
        $second = $this->makeConnection();

        ConnectionManager::addConnection('first', $first);
        ConnectionManager::addConnection('second', $second);

        $this->assertSame($first, ConnectionManager::getConnection());

        ConnectionManager::addConnection('third', $second, true);

        $this->assertSame($second, ConnectionManager::getConnection());
    }

    public function testSetDefaultConnectionByName(): void
    {
        $first = $this->makeConnection();
        $second = $this->makeConnection();

        ConnectionManager::addConnection('first', $first);
        ConnectionManager::addConnection('second', $second);
        ConnectionManager::setDefaultConnection('second');

        $this->assertSame($second, ConnectionManager::getConnection());
    }

    public function testSetDefaultConnectionForUnknownNameThrows(): void
    {
        $this->expectException(ConnectException::class);

        ConnectionManager::setDefaultConnection('missing');
    }

    public function testSetDefaultConnectionObjectRegistersUnknownConnectionUnderDefaultKey(): void
    {
        $connection = $this->makeConnection();

        ConnectionManager::setDefaultConnectionObject($connection);

        $this->assertSame($connection, ConnectionManager::getConnection());
        $this->assertTrue(ConnectionManager::hasConnection('default'));
    }

    public function testSetDefaultConnectionObjectDoesNotDuplicateExistingConnection(): void
    {
        $connection = $this->makeConnection();
        ConnectionManager::addConnection('named', $connection);

        ConnectionManager::setDefaultConnectionObject($connection);

        $this->assertSame(['named'], ConnectionManager::getConnectionNames());
    }

    public function testHasConnection(): void
    {
        $this->assertFalse(ConnectionManager::hasConnection('primary'));

        ConnectionManager::addConnection('primary', $this->makeConnection());

        $this->assertTrue(ConnectionManager::hasConnection('primary'));
    }

    public function testGetConnectionNames(): void
    {
        ConnectionManager::addConnection('one', $this->makeConnection());
        ConnectionManager::addConnection('two', $this->makeConnection());

        $this->assertSame(['one', 'two'], ConnectionManager::getConnectionNames());
    }
}
