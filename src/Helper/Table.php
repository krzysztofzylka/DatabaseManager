<?php

namespace krzysztofzylka\DatabaseManager\Helper;

class Table
{

    /**
     * Prepare column name with alias
     * @param string $name
     * @return string
     */
    public static function prepareColumnNameWithAlias(string $name): string
    {
        if (str_contains($name, '.')) {
            $explode = explode('.', $name);

            return '`' . $explode[0] . '`.`' . $explode[1] . '`';
        }

        return '`' . $name . '`';
    }

    /**
     * Prepare custom column list
     * @param array $columns
     * @param string|null $tableName
     * @return string
     */
    public static function prepareCustomColumnList(array $columns, ?string $tableName) : string {
        foreach ($columns as $id => $column) {
            if (!str_contains($column, '.')) {
                $column = $tableName . '.' . $column;
            }

            $columns[$id] = Table::prepareColumnNameWithAlias($column) . ' as `' . $column . '`';
        }

        return implode(', ', $columns);
    }

    /**
     * Prepare column list for select
     * @return string
     * @throws TableException
     */
    private function prepareColumnListForSql() : string {
        $columnList = $this->prepareColumnList(false);

        if (isset($this->bind)) {
            foreach ($this->bind as $bind) {
                $bindTable = (new \krzysztofzylka\DatabaseManager\Table())->setName($bind['tableName']);
                $columnList = array_merge($columnList, $bindTable->prepareColumnList(false));
            }
        }

        return implode(', ', $columnList);
    }


}