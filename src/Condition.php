<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Helper\Table as TableHelper;
use krzysztofzylka\DatabaseManager\Trait\ConditionMethods;

class Condition
{

    use ConditionMethods;

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
     * Database type
     * @var DatabaseType
     */
    protected DatabaseType $databaseType = DatabaseType::mysql;

    /**
     * Create condition
     * @param string $column
     * @param string $operator
     * @param mixed $value
     */
    public function __construct(string $column, string $operator, mixed $value = null)
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * Set database type
     * @param DatabaseType $databaseType
     * @return $this
     */
    public function setDatabaseType(DatabaseType $databaseType): self
    {
        $this->databaseType = $databaseType;

        return $this;
    }

    /**
     * Get column name
     * @param bool $raw
     * @return string
     */
    public function getColumn(bool $raw = false): string
    {
        if ($raw) {
            return $this->column;
        }

        $quote = $this->databaseType === DatabaseType::postgres ? '"' : '`';
        return TableHelper::prepareColumnNameWithAlias($this->column, $quote);
    }

    /**
     * Generate conditions
     * @return string
     */
    public function __toString()
    {
        return trim(
            string: $this->getColumn() . ' ' . $this->operator . ' ' . $this->prepareValue($this->value)
        );
    }

    /**
     * Get value
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Set value
     * @param mixed $value
     * @return $this
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get operator
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

}