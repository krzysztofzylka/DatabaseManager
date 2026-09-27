<?php

namespace krzysztofzylka\DatabaseManager\Enum;

use krzysztofzylka\DatabaseManager\Dialect\DatabaseDialect;
use krzysztofzylka\DatabaseManager\Dialect\MysqlDialect;
use krzysztofzylka\DatabaseManager\Dialect\PostgresDialect;
use krzysztofzylka\DatabaseManager\Dialect\SqliteDialect;

/**
 * Database types
 */
enum DatabaseType
{

    case mysql;

    case sqlite;

    case postgres;

    /**
     * SQL dialect of this database type
     * @return DatabaseDialect
     */
    public function getDialect(): DatabaseDialect
    {
        static $dialects = [];

        return $dialects[$this->name] ??= match ($this) {
            self::mysql => new MysqlDialect(),
            self::sqlite => new SqliteDialect(),
            self::postgres => new PostgresDialect(),
        };
    }

}
