<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Helper\Table as TableHelper;

/**
 * Tworzenie warunków dla biblioteki Database
 */
class Condition {

    /**
     * Column name
     * @var string
     */
    protected string $column;

    /**
     * Operator
     * @var string
     */
    protected string $operator;

    /**
     * Value
     * @var mixed
     */
    protected mixed $value;

    /**
     * Create condition
     * @param string $column
     * @param string $operator
     * @param mixed $value
     */
    public function __construct(string $column, string $operator, mixed $value = null) {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * Generate conditions
     * @return string
     */
    public function __toString() {
        return $this->getColumn() . ' ' . $this->operator . ' ' . Helper\Value::prepareValue($this->value);
    }

    /**
     * Get column name
     * @return string
     */
    public function getColumn() : string {
        return TableHelper::prepareColumnNameWithAlias($this->column);
    }

}