<?php

namespace krzysztofzylka\DatabaseManager\Trait;

trait ConditionMethods
{

    /**
     * Get prepare value
     * @param mixed $value
     * @return string
     * @ignore
     */
    private function prepareValue(mixed $value): string
    {
        switch (gettype($value)) {
            case 'array':
                return '(\'' . implode('\', \'', $value) . '\')';
            case 'integer':
                return $value;
            case 'NULL':
                return 'NULL';
            default:
                if ($value === 'IS NULL') {
                    return $value;
                } elseif (str_contains($value, '"')) {
                    return "'" . $value . "'";
                } else {
                    return '"' . $value . '"';
                }
        }
    }

}