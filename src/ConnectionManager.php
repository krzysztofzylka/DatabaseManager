<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Exception\ConnectException;

/**
 * ConnectionManager - manages multiple database connections
 */
class ConnectionManager
{

    /**
     * Stores all connections
     * @var array<string, DatabaseConnect>
     */
    private static array $connections = [];

    /**
     * Default connection (for backward compatibility)
     * @var DatabaseConnect|null
     */
    private static ?DatabaseConnect $defaultConnection = null;

    /**
     * Adds a new connection
     * @param string $name Connection name
     * @param DatabaseConnect $connection Connection object
     * @param bool $setAsDefault Set as default connection
     * @return void
     */
    public static function addConnection(string $name, DatabaseConnect $connection, bool $setAsDefault = false): void
    {
        self::$connections[$name] = $connection;

        if ($setAsDefault || is_null(self::$defaultConnection)) {
            self::$defaultConnection = $connection;
        }
    }

    /**
     * Gets a connection by name
     * @param string|null $name Connection name (null for default connection)
     * @return DatabaseConnect
     * @throws ConnectException
     */
    public static function getConnection(?string $name = null): DatabaseConnect
    {
        if (is_null($name)) {
            if (is_null(self::$defaultConnection)) {
                throw new ConnectException("No default connection has been set");
            }

            return self::$defaultConnection;
        }

        if (!isset(self::$connections[$name])) {
            throw new ConnectException("Connection '$name' not found");
        }

        return self::$connections[$name];
    }

    /**
     * Sets the default connection by name
     * @param string $name Connection name
     * @return void
     * @throws ConnectException
     */
    public static function setDefaultConnection(string $name): void
    {
        if (!isset(self::$connections[$name])) {
            throw new ConnectException("Connection '$name' not found");
        }

        self::$defaultConnection = self::$connections[$name];
    }

    /**
     * Sets the default connection directly by object
     * @param DatabaseConnect $connection
     * @return void
     */
    public static function setDefaultConnectionObject(DatabaseConnect $connection): void
    {
        self::$defaultConnection = $connection;
        $found = false;

        foreach (self::$connections as $key => $conn) {
            if ($conn === $connection) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            self::$connections['default'] = $connection;
        }
    }

    /**
     * Checks if a connection with the given name exists
     * @param string $name Connection name
     * @return bool
     */
    public static function hasConnection(string $name): bool
    {
        return isset(self::$connections[$name]);
    }

    /**
     * Gets a list of all connection names
     * @return array
     */
    public static function getConnectionNames(): array
    {
        return array_keys(self::$connections);
    }

}