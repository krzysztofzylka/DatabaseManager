<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class PriceColumn extends Column
{

    public function __construct(?string $name = 'price', string $size = '10,2', ?float $default = null)
    {
        parent::__construct($name, ColumnType::decimal, $size);

        $this->setNull(false);
        
        if (!is_null($default)) {
            $this->setDefault($default);
        }
    }

}
