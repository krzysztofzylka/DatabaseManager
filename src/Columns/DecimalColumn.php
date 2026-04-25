<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class DecimalColumn extends Column
{

    public function __construct(string $name, string $size = '16,2', ?float $default = null)
    {
        parent::__construct($name, ColumnType::decimal, $size);
        
        if (!is_null($default)) {
            $this->setDefault($default);
        }
    }

}
