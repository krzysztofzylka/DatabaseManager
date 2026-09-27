<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class IdColumn extends Column
{

    public function __construct()
    {
        // Each dialect maps this to its native auto-increment key (e.g. INTEGER PRIMARY KEY on SQLite)
        parent::__construct('id', ColumnType::bigint, null);

        $this->setUnsigned(true)
            ->setNull(false)
            ->setAutoincrement(true)
            ->setPrimary(true);
    }

}
