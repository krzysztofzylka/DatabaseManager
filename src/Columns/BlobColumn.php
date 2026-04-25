<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;

class BlobColumn extends Column
{

    public function __construct(?string $name = 'blob', bool $nullable = true)
    {
        parent::__construct($name, ColumnType::blob, null);

        $this->setNull($nullable);
    }

}
