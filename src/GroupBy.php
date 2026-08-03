<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Helper\SqlExpr;
use krzysztofzylka\DatabaseManager\Helper\SqlIdentifier;

/**
 * Structured, validated alternative to passing a raw GROUP BY string to
 * Table::findAll()/findCount(). The raw string parameter still works and is not removed.
 */
final class GroupBy
{

    private function __construct(
        private readonly string $sql
    ) {
    }

    /**
     * Group by a validated column name (optionally table.column)
     * @param string $column
     * @return self
     */
    public static function column(string $column): self
    {
        SqlIdentifier::validateQualified($column);

        return new self($column);
    }

    /**
     * Group by a raw SQL expression (e.g. a function call) instead of a plain column
     * @param SqlExpr $expression
     * @return self
     */
    public static function expression(SqlExpr $expression): self
    {
        return new self((string)$expression);
    }

    public function __toString(): string
    {
        return $this->sql;
    }

}
