<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class IntColumn extends Column
{

    public function __construct(string $name, bool $nullable = true, bool $unsigned = false)
    {
        parent::__construct($name, ColumnType::int, null);

        $this->setNull($nullable)
            ->setUnsigned($unsigned);
    }

}
