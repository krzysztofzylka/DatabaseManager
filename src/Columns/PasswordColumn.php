<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class PasswordColumn extends Column
{

    public function __construct(?string $name = 'password', int $size = 255, bool $nullable = true)
    {
        parent::__construct($name, ColumnType::varchar, $size);

        $this->setNull($nullable);
    }

}
