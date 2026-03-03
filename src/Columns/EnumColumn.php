<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class EnumColumn extends Column
{
    public function __construct(string $name, array $values, ?string $default = null)
    {
        parent::__construct($name, ColumnType::enum, $values);
        
        if (!is_null($default)) {
            $this->setDefault($default);
        }
    }
}
