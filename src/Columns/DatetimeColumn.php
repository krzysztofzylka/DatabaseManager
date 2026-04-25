<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class DatetimeColumn extends Column
{

    public function __construct(string $name, ?string $default = null)
    {
        parent::__construct($name, ColumnType::datetime, null);
        
        if (!is_null($default)) {
            $this->setDefault($default);
        }
    }

}
