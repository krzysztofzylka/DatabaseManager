<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseException;
use krzysztofzylka\SimpleLibraries\Library\Cache;
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
     * Cache
     * @var Cache
     */
    public static Cache $cache;

    /**
     * Initialize
     */
    public function __construct()
    {
        if (!isset(self::$cache)) {
            self::$cache = new Cache();
        }
    }

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

    /**
     * Prepare
     * @param string $query
     * @param array $options
     * @return false|PDOStatement
     */
    public static function prepare(string $query, array $options = []): bool|PDOStatement
    {
        self::setLastSql($query);

        return self::getPdoInstance()->prepare($query, $options);
    }

    /**
     * Get connection ID
     * @return int
     */
    public static function getConnectionId(): int
    {
        return self::query('SELECT CONNECTION_ID();')->fetch(PDO::FETCH_ASSOC)['CONNECTION_ID()'];
    }

    /**
     * Get database type
     * @return DatabaseType
     */
    public static function getDatabaseType(): DatabaseType
    {
        return self::$databaseConnect->getType();
    }

}