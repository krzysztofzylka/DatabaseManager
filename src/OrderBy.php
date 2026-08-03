<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\SortDirection;
use krzysztofzylka\DatabaseManager\Helper\SqlExpr;
use krzysztofzylka\DatabaseManager\Helper\SqlIdentifier;

/**
 * Structured, validated alternative to passing a raw ORDER BY string to
 * Table::find()/findAll(). The raw string parameter still works and is not removed.
 */
final class OrderBy
{

    private function __construct(
        private readonly string $sql
    ) {
    }

    /**
     * Sort by a validated column name (optionally table.column)
     * @param string $column
     * @param SortDirection $direction
     * @return self
     */
    public static function column(string $column, SortDirection $direction = SortDirection::asc): self
    {
        SqlIdentifier::validateQualified($column);

        return new self($column . ' ' . $direction->value);
    }

    /**
     * Sort by a raw SQL expression (e.g. a function call) instead of a plain column
     * @param SqlExpr $expression
     * @param SortDirection $direction
     * @return self
     */
    public static function expression(SqlExpr $expression, SortDirection $direction = SortDirection::asc): self
    {
        return new self((string)$expression . ' ' . $direction->value);
    }

    public function __toString(): string
    {
        return $this->sql;
    }

}
