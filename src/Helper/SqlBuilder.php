<?php

namespace krzysztofzylka\DatabaseManager\Helper;

class SqlBuilder
{

    /**
     * Select generator
     * @param $columns
     * @param $from
     * @param $join
     * @param $where
     * @param $groupBy
     * @param $orderBy
     * @param $limit
     * @return string
     */
    public static function select($columns, $from, $join = null, $where = null, $groupBy = null, $orderBy = null, $limit = null) : string
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

}