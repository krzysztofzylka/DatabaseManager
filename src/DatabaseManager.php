<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Exception\DatabaseException;
use PDO;
use PDOStatement;

class DatabaseManager
{

    /**
     * Database connect instance
     * @var DatabaseConnect
     */
    private static DatabaseConnect $databaseConnect;

    /**
     * PDO Instance
     * @var PDO
     */
    private static PDO $pdoInstance;

    /**
     * Last SQL
     * @var string|null
     */
    private static ?string $lastSql = null;

    /**
     * Last SQL list
     * @var array
     */
    private static array $lastSqlList = [];

    /**
     * Connect to database
     * @param DatabaseConnect $databaseConnect database connect instance
     * @return void
     * @throws DatabaseException
     */
    public function connect(DatabaseConnect $databaseConnect): void
    {
        self::$databaseConnect = $databaseConnect;
        self::$databaseConnect->connect();
        self::$pdoInstance = self::$databaseConnect->getPdoInstance();
    }

    /**
     * Get PDO instance
     * @return PDO
     */
    public static function getPdoInstance(): PDO
    {
        return self::$pdoInstance;
    }

    /**
     * Set last sql list
     * @param string $sql
     * @return void
     */
    public static function setLastSql(string $sql): void
    {
        self::$lastSql = $sql;

        if (self::$databaseConnect->isDebug()) {
            self::$lastSqlList[] = $sql;
        }
    }

    /**
     * Get last sql
     * @return string|null
     */
    public static function getLastSql(): ?string
    {
        return self::$lastSql;
    }

    /**
     * Get last sql lists
     * @return array
     */
    public static function getLastSqlList(): array
    {
        return self::$lastSqlList;
    }

    /**
     * Query
     * @param string $sql
     * @param int|null $fetchMode
     * @param mixed ...$fetch_mode_args
     * @return false|PDOStatement
     */
    public static function query(string $sql, int|null $fetchMode = null, mixed ...$fetch_mode_args): bool|PDOStatement
    {
        self::setLastSql($sql);

        return self::getPdoInstance()->query($sql, $fetchMode, $fetch_mode_args);
    }

}