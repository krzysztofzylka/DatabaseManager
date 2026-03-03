<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class UuidColumn extends Column
{
    public function __construct(?string $name = 'uuid', bool $nullable = false)
    {
        parent::__construct($name, ColumnType::varchar, 36);
        $this->setNull($nullable);
    }
}
