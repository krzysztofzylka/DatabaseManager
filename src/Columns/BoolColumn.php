<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class BoolColumn extends Column
{
    public function __construct(string $name, bool $default = false)
    {
        parent::__construct($name, ColumnType::tinyint, 1);
        $this->setDefault($default ? 1 : 0);
    }
}
