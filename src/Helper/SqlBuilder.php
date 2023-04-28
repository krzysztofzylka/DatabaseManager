<?php

namespace krzysztofzylka\DatabaseManager\Helper;

class SqlBuilder {

    /**
     * Select generator
     * @param string $columns
     * @param string $from
     * @param string|null $join
     * @param string|null $where
     * @param string|null $groupBy
     * @param string|null $orderBy
     * @param string|null $limit
     * @return string
     */
    public static function select(
        string $columns,
        string $from,
        ?string $join = null,
        ?string $where = null,
        ?string $groupBy = null,
        ?string $orderBy = null,
        ?string $limit = null
    ): string
    {
        if (!str_starts_with($from, '`') && !str_ends_with($from, '`')) {
            $from = '`' . $from . '`';
        }

        $sql = 'SELECT ' . $columns . ' FROM ' . $from;

        if (!is_null($join)) {
            $sql .= ' ' . $join;
        }

        if (!is_null($where)) {
            $sql .= ' WHERE ' . $where;
        }

        if (!is_null($groupBy)) {
            $sql .= ' GROUP BY ' . $groupBy;
        }

        if (!is_null($orderBy)) {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        if (!is_null($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }

        return $sql;
    }

    /**
     * Create index
     * @param string $name
     * @param string $tableName
     * @param array $columns
     * @return string
     */
    public static function createIndex(string $name, string $tableName, array $columns = ['id']): string
    {
        return 'CREATE INDEX ' . $name . ' ON ' . $tableName . '(' . implode(', ' , $columns) . ')';
    }

    /**
     * Show tables
     * @param string|null $likeTableName
     * @return string
     */
    public static function showTables(?string $likeTableName = null): string
    {
        $sql = 'SHOW TABLES';

        if ($likeTableName) {
            $sql .= ' LIKE "' . $likeTableName . '"';
        }

        return $sql;
    }

    /**
     * Delete
     * @param string $tableName
     * @param ?string $where
     * @return string
     */
    public static function delete(string $tableName, ?string $where = null): string
    {
        $sql = 'DELETE FROM ' . $tableName;

        if (!is_null($where)) {
            $sql .= ' WHERE ' . $where;
        }

        return $sql;
    }

}