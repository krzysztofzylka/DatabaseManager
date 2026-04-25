<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class DateColumn extends Column
{

    public function __construct(string $name, ?string $default = null)
    {
        parent::__construct($name, ColumnType::date, null);
        
        if (!is_null($default)) {
            $this->setDefault($default);
        }
    }

}
