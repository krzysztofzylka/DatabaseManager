<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class EmailColumn extends Column
{

    public function __construct(?string $name = 'email', int $size = 255, bool $nullable = true)
    {
        parent::__construct($name, ColumnType::varchar, $size);

        $this->setNull($nullable);
    }

}
