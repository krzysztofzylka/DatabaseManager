<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Exception\ConditionException;
use krzysztofzylka\DatabaseManager\Trait\ConditionMethods;

class Condition {

    use ConditionMethods;

    private array $conditions = [];

    /**
     * Where
     * @param string|Condition $name name or \Conditions
     * @param mixed $value
     * @param string $operator
     * @return Condition
     */
    public function where(string|Condition $name, mixed $value = null, string $operator = '=') : self {
        if ($name instanceof Condition) {
            $this->conditions[] = $name;

            return $this;
        }

        $this->conditions[] = [
            'name' => $name,
            'value' => $value,
            'operator' => $operator
        ];

        return $this;
    }

    /**
     * AND where
     * @param string|Condition $name name or \Conditions
     * @param mixed $value
     * @param string $operator
     * @return Condition
     */
    public function andWhere(string|Condition $name, mixed $value = null, string $operator = '=') : self {
        if ($name instanceof Condition) {
            $this->conditions['AND'][] = $name;

            return $this;
        }

        $this->conditions['AND'][] = [
            'name' => $name,
            'value' => $value,
            'operator' => $operator
        ];

        return $this;
    }

    /**
     * OR where
     * @param string|Condition $name name or \Conditions
     * @param mixed $value
     * @param string $operator
     * @return Condition
     */
    public function orWhere(string|Condition $name, mixed $value = null, string $operator = '=') : self {
        if ($name instanceof Condition) {
            $this->conditions['OR'][] = $name;

            return $this;
        }

        $this->conditions['OR'][] = [
            'name' => $name,
            'value' => $value,
            'operator' => $operator
        ];

        return $this;
    }

    /**
     * Get conditions
     * @return array
     */
    public function getConditions() : array {
        return $this->conditions;
    }

    /**
     * Get prepared conditions
     * @param ?array $data
     * @param string $type (default AND)
     * @return string
     * @throws ConditionException
     */
    public function getPrepareConditions(?array $data = null, string $type = 'AND') : string {
        try {
            $data = $data ?? $this->getConditions();
            $sqlArray = [];

            foreach ($data as $nextType => $conditionValue) {
                if (isset($conditionValue[0]) && $conditionValue[0] instanceof Condition) {
                    $sqlArray[] = $this->getPrepareConditions($conditionValue[0]->getConditions(), $nextType);
                } else {
                    $operator = ' ' . $conditionValue['operator'] . ' ';

                    if ($conditionValue['value'] === 'IS NULL') {
                        $operator = ' ';
                    }

                    $sqlArray[] = $conditionValue['name'] . $operator . $this->prepareValue($conditionValue['value']);
                }
            }

            return '(' . implode(" $type ", $sqlArray) . ')';
        } catch (\Exception $exception) {
            throw new ConditionException($exception->getMessage());
        }
    }

}