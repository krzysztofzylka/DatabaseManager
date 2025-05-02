<?php

namespace krzysztofzylka\DatabaseManager\Helper;

use krzysztofzylka\DatabaseManager\Enum\DatabaseType;

class SqlBuilder
{

    private static array $cache = [];

    private static int $maxCacheSize = 100;

    /**
     * Generates SELECT query
     * @param string $columns Columns to select
     * @param string $from Table name
     * @param string|null $join JOIN clause
     * @param string|null $where WHERE clause
     * @param string|null $groupBy GROUP BY clause
     * @param string|null $orderBy ORDER BY clause
     * @param string|null $limit LIMIT clause
     * @param DatabaseType $databaseType Database type
     * @return string Generated SQL query
     */
    public static function select(
        string $columns,
        string $from,
        ?string $join = null,
        ?string $where = null,
        ?string $groupBy = null,
        ?string $orderBy = null,
        ?string $limit = null,
        ?DatabaseType $databaseType = null
    ): string {
        $databaseType = $databaseType ?? DatabaseType::mysql;
        $cacheKey = md5(serialize(func_get_args()));

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $quote = $databaseType === DatabaseType::postgres ? '"' : '`';
        $sql = 'SELECT ' . $columns . ' FROM ' . self::prepareTableName($from, $quote);

        if ($join !== null) {
            $sql .= ' ' . self::cleanSql($join);
        }

        if ($where !== null) {
            $sql .= ' WHERE ' . self::cleanSql($where);
        }

        if ($groupBy !== null) {
            $sql .= ' GROUP BY ' . self::cleanSql($groupBy);
        }

        if ($orderBy !== null) {
            $sql .= ' ORDER BY ' . self::cleanSql($orderBy);
        }

        if ($limit !== null) {
            $sql .= ' LIMIT ' . $limit;
        }

        $sql = trim($sql);
        self::addToCache($cacheKey, $sql);

        return $sql;
    }

    /**
     * Generates INSERT query
     * @param string $table Table name
     * @param array $columns Column names
     * @param array $values Values or placeholders
     * @param DatabaseType $databaseType Database type
     * @return string Generated SQL query
     */
    public static function insert(string $table, array $columns, array $values, ?DatabaseType $databaseType = null): string
    {
        $databaseType = $databaseType ?? DatabaseType::mysql;
        $cacheKey = md5('insert_' . $table . serialize($columns) . serialize($values) . ($databaseType ? $databaseType->name : 'mysql'));

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $quote = $databaseType === DatabaseType::postgres ? '"' : '`';
        $sql = 'INSERT INTO ' . self::prepareTableName($table, $quote);
        $sql .= ' (' . implode(', ', array_map(fn($col) => $quote . $col . $quote, $columns)) . ')';
        $sql .= ' VALUES (' . implode(', ', $values) . ')';

        self::addToCache($cacheKey, $sql);

        return $sql;
    }

    /**
     * Generates UPDATE query
     * @param string $table Table name
     * @param array $setValues Column => value/placeholder pairs
     * @param string|null $where WHERE clause
     * @param null|DatabaseType $databaseType Database type
     * @return string Generated SQL query
     */
    public static function update(string $table, array $setValues, ?string $where = null, ?DatabaseType $databaseType = null): string
    {
        $databaseType = $databaseType ?? DatabaseType::mysql;
        $cacheKey = md5('update_' . $table . serialize($setValues) . $where . ($databaseType ? $databaseType->name : 'mysql'));

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $quote = $databaseType === DatabaseType::postgres ? '"' : '`';
        $setParts = [];

        foreach ($setValues as $column => $value) {
            $setParts[] = $quote . $column . $quote . " = $value";
        }

        $sql = 'UPDATE ' . self::prepareTableName($table, $quote);
        $sql .= ' SET ' . implode(', ', $setParts);

        if ($where !== null) {
            $sql .= ' WHERE ' . self::cleanSql($where);
        }

        self::addToCache($cacheKey, $sql);

        return $sql;
    }

    /**
     * Generates DELETE query
     * @param string $table Table name
     * @param string|null $where WHERE clause
     * @param DatabaseType $databaseType Database type
     * @return string Generated SQL query
     */
    public static function delete(string $table, ?string $where = null, ?DatabaseType $databaseType = null): string
    {
        $databaseType = $databaseType ?? DatabaseType::mysql;
        $cacheKey = md5('delete_' . $table . $where . ($databaseType ? $databaseType->name : 'mysql'));

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $quote = $databaseType === DatabaseType::postgres ? '"' : '`';
        $sql = 'DELETE FROM ' . self::prepareTableName($table, $quote);

        if ($where !== null) {
            $sql .= ' WHERE ' . self::cleanSql($where);
        }

        self::addToCache($cacheKey, $sql);

        return $sql;
    }

    /**
     * Sets maximum cache size
     * @param int $size New cache size
     * @return void
     */
    public static function setMaxCacheSize(int $size): void
    {
        self::$maxCacheSize = $size;
        self::trimCache();
    }

    /**
     * Clears query cache
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * @param string $name Table name
     * @param string $quote Quote character
     * @return string
     */
    private static function prepareTableName(string $name, string $quote = '`'): string
    {
        if ((str_starts_with($name, '`') && str_ends_with($name, '`')) ||
            (str_starts_with($name, '"') && str_ends_with($name, '"'))) {

            return $name;
        }

        return $quote . $name . $quote;
    }

    private static function cleanSql(string $sql): string
    {
        return preg_replace('/\s+/', ' ', trim($sql));
    }

    private static function addToCache(string $key, string $sql): void
    {
        self::$cache[$key] = $sql;
        self::trimCache();
    }

    private static function trimCache(): void
    {
        if (count(self::$cache) > self::$maxCacheSize) {
            self::$cache = array_slice(self::$cache, -intval(self::$maxCacheSize / 2), null, true);
        }
    }

}