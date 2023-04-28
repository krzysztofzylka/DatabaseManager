<?php

namespace krzysztofzylka\DatabaseManager\Enum;

/**
 * Database types
 */
enum DatabaseType
{

    /**
     * Mysql
     */
    case mysql;

    /**
     * SQLite
     */
    case sqlite;

}