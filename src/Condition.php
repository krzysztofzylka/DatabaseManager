<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
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
     * @deprecated Embeds the value directly into the SQL string instead of binding it.
     * Use toSql() for parameterized output; this method is kept only for backward compatibility.
     */
    public function __toString()
    {
        return trim(
            string: $this->getColumn() . ' ' . $this->operator . ' ' . $this->prepareValue($this->value)
        );
    }

    /**
     * Compile the condition to parameterized SQL instead of embedding the value directly.
     * @param int $bindIndex shared placeholder counter, incremented for each bound value
     * @return array{sql: string, bind: array} sql fragment and its bind values
     * @throws DatabaseManagerException if the operator is not on the allowed list
     */
    public function toSql(int &$bindIndex = 0): array
    {
        $column = $this->getColumn();
        $normalizedOperator = strtoupper(trim($this->operator));

        if (!in_array($normalizedOperator, [
            '=', '!=', '<>', '>', '<', '>=', '<=',
            'LIKE', 'NOT LIKE', 'IS', 'IS NOT', 'IN', 'NOT IN'
        ], true)) {
            throw new DatabaseManagerException("Unsupported condition operator: {$this->operator}");
        }

        if (is_null($this->value) && ($normalizedOperator === 'IS' || $normalizedOperator === 'IS NOT')) {
            return [
                'sql' => trim($column . ' ' . $this->operator . ' NULL'),
                'bind' => []
            ];
        }

        if (is_array($this->value) && ($normalizedOperator === 'IN' || $normalizedOperator === 'NOT IN')) {
            $bind = [];
            $placeholders = [];

            foreach ($this->value as $item) {
                $key = ':bind_' . $bindIndex++;
                $placeholders[] = $key;
                $bind[$key] = $item;
            }

            return [
                'sql' => trim($column . ' ' . $this->operator . ' (' . implode(', ', $placeholders) . ')'),
                'bind' => $bind
            ];
        }

        $key = ':bind_' . $bindIndex++;

        return [
            'sql' => trim($column . ' ' . $this->operator . ' ' . $key),
            'bind' => [$key => $this->value]
        ];
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