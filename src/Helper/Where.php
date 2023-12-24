<?php

namespace krzysztofzylka\DatabaseManager\Helper;

use Exception;
use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Trait\ConditionMethods;

class Where
{

    use ConditionMethods;

    /**
     * Prepare conditions
     * @param array $data
     * @param string $type
     * @return string
     * @throws DatabaseManagerException
     */
    public function getPrepareConditions(array $data, string $type = 'AND'): string
    {
        try {
            $sqlArray = [];

            foreach ($data as $nextType => $conditionValue) {
                if ($conditionValue instanceof Condition) {
                    $sqlArray[] = (string)$conditionValue;
                } elseif (is_array($conditionValue)) {
                    $sqlArray[] = $this->getPrepareConditions($conditionValue, $nextType);
                } else {
                    $sqlArray[] = Table::prepareColumnNameWithAlias($nextType) . ' = ' . $this->prepareValue($conditionValue);
                }
            }

            return '(' . implode(" $type ", $sqlArray) . ')';
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

}