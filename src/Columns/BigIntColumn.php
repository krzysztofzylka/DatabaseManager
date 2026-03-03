<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class BigIntColumn extends Column
{
    public function __construct(string $name, bool $nullable = true, bool $unsigned = true)
    {
        parent::__construct($name, ColumnType::bigint, null);
        $this->setNull($nullable)
            ->setUnsigned($unsigned);
    }
}
