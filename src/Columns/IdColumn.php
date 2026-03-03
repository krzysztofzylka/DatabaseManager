<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;

class IdColumn extends Column
{
    public function __construct()
    {
        parent::__construct('id', ColumnType::bigint, null);
        
        if (DatabaseManager::getDatabaseType() === DatabaseType::sqlite) {
            parent::__construct('id', ColumnType::integer, null);
        }
        
        $this->setUnsigned(true)
            ->setNull(false)
            ->setAutoincrement(true)
            ->setPrimary(true);
    }
}
