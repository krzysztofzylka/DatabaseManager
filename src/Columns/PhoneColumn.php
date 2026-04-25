<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class PhoneColumn extends Column
{

    public function __construct(?string $name = 'phone', bool $nullable = true)
    {
        parent::__construct($name, ColumnType::int, 26);

        $this->setNull($nullable);
    }

}
