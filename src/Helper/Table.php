<?php

namespace krzysztofzylka\DatabaseManager\Helper;

class Table
{

    /**
     * Prepare column name with alias
     * @param string $name
     * @param string $quote
     * @return string
     */
    public static function prepareColumnNameWithAlias(string $name, string $quote = '`'): string
    {
        if (str_contains($name, '.')) {
            $explode = explode('.', $name);

            return $quote . $explode[0] . $quote . '.' . $quote . $explode[1] . $quote;
        }

        return $quote . $name . $quote;
    }

}