<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class VarcharColumn extends Column
{
    public function __construct(string $name, int $size = 255, bool $nullable = true, ?string $default = null)
    {
        parent::__construct($name, ColumnType::varchar, $size);
        $this->setNull($nullable);
        
        if (!is_null($default)) {
            $this->setDefault($default);
        }
    }
}
