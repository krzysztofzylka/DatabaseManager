<?php

namespace krzysztofzylka\DatabaseManager\Columns;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Enum\ColumnDefault;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\Trigger;

class DateModifyColumn extends Column
{

    public function __construct(?string $name = 'date_modify')
    {
        parent::__construct($name, ColumnType::datetime, null);

        $this->setDefault(ColumnDefault::currentTimestamp)
            ->setNull(false)
            ->addTrigger(Trigger::UpdateTimestampAfterUpdate);
    }

}
