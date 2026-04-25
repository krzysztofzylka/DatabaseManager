<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class JsonColumn extends Column
{

    public function __construct(?string $name = 'json', bool $nullable = true)
    {
        parent::__construct($name, ColumnType::json, null);

        $this->setNull($nullable);
    }

}
