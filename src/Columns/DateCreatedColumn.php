<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnDefault;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class DateCreatedColumn extends Column
{
    public function __construct(?string $name = 'date_created')
    {
        parent::__construct($name, ColumnType::datetime, null);
        $this->setDefault(ColumnDefault::currentTimestamp)
            ->setNull(false);
    }
}
