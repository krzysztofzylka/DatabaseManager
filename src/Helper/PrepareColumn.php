<?php

namespace krzysztofzylka\DatabaseManager\Helper;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;

class PrepareColumn
{

    /**
     * Generate column sql
     * @param Column $column
     * @param DatabaseType|null $databaseType defaults to the default connection's type
     * @return string
     * @throws ConnectException
     */
    public static function generateCreateColumnSql(Column $column, ?DatabaseType $databaseType = null): string
    {
        return ($databaseType ?? DatabaseManager::getDatabaseType())->getDialect()->columnDefinition($column);
    }

}
