<?php

namespace krzysztofzylka\DatabaseManager\Helper;

class Table {

    /**
     * Prepare column name with alias
     * @param string $name
     * @return string
     */
    public static function prepareColumnNameWithAlias(string $name) : string {
        if (str_contains($name, '.')) {
            $explode = explode('.', $name);

            return '`' . $explode[0] . '`.`' . $explode[1] . '`';
        }

        return '`' . $name . '`';
    }

}