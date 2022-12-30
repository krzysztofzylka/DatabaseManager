<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Exception\ConditionException;

class Condition {

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
                    $sqlArray[] = $conditionValue['name'] . ' ' . $conditionValue['operator'] . ' ' . $this->prepareValue($conditionValue['value']);
                }
            }

            return '(' . implode(" $type ", $sqlArray) . ')';
        } catch (\Exception $exception) {
            throw new ConditionException($exception->getMessage());
        }
    }

    /**
     * Get prepare value
     * @param mixed $value
     * @return string
     * @ignore
     */
    private function prepareValue(mixed $value) : string {
        switch (gettype($value)) {
            case 'array':
                return '(\'' . implode('\', \'', $value) . '\')';
            case 'integer':
                return $value;
            case 'NULL':
                return 'NULL';
            default:
                if (str_contains($value, '"')) {
                    return "'" . $value . "'";
                } else {
                    return '"' . $value . '"';
                }
        }
    }

}