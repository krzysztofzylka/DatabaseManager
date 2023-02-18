<?php

namespace krzysztofzylka\DatabaseManager\Helper;

use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Exception\ConditionException;
use krzysztofzylka\DatabaseManager\Trait\ConditionMethods;

class SimpleCondition {

    use ConditionMethods;

    public function getPrepareConditions(array $data, string $type = 'AND') : string {
        try {
            $sqlArray = [];

            foreach ($data as $nextType => $conditionValue) {
                if (is_array($conditionValue)) {
                    $sqlArray[] = $this->getPrepareConditions($conditionValue, $nextType);
                } else {
                    $sqlArray[] = $nextType . ' = ' . $this->prepareValue($conditionValue);
                }
            }

            return '(' . implode(" $type ", $sqlArray) . ')';
        } catch (\Exception $exception) {
            throw new ConditionException($exception->getMessage());
        }
    }

}