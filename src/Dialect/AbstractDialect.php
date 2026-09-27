<?php

namespace krzysztofzylka\DatabaseManager\Dialect;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\ColumnDefault;
use krzysztofzylka\DatabaseManager\Enum\Trigger;
use PDO;
use PDOStatement;

/**
 * Behaviour shared by most engines
 */
abstract class AbstractDialect implements DatabaseDialect
{

    public function quoteIdentifier(string $name): string
    {
        $quote = $this->getIdentifierQuote();

        return implode('.', array_map(fn($segment) => $quote . $segment . $quote, explode('.', $name)));
    }

    public function primaryKeyClause(Column $column): ?string
    {
        return null;
    }

    public function triggerSql(Trigger $trigger, string $tableName, string $columnName): ?string
    {
        return null;
    }

    public function createTableSql(string $tableName, array $columnDefinitions, array $constraints = []): string
    {
        return 'CREATE TABLE ' . $this->quoteIdentifier($tableName)
            . ' (' . implode(', ', array_merge($columnDefinitions, $constraints)) . ')';
    }

    public function insertReturningClause(): string
    {
        return '';
    }

    public function lastInsertId(PDOStatement $statement, PDO $pdo): mixed
    {
        return $pdo->lastInsertId();
    }

    /**
     * Run a query with bound values and log it as the last SQL
     * @param PDO $pdo
     * @param string $sql
     * @param array $params
     * @return PDOStatement|false
     */
    protected function run(PDO $pdo, string $sql, array $params = []): PDOStatement|false
    {
        DatabaseManager::setLastSql($sql);

        if (empty($params)) {
            return $pdo->query($sql);
        }

        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * Fetch rows one by one until the statement is exhausted
     * @param PDOStatement|false $statement
     * @param int $mode
     * @return array
     */
    protected function fetchRows(PDOStatement|false $statement, int $mode = PDO::FETCH_ASSOC): array
    {
        $rows = [];

        if ($statement === false) {
            return $rows;
        }

        while ($row = $statement->fetch($mode)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Join non-empty parts of a column definition with single spaces
     * @param array $parts
     * @return string
     */
    protected function joinParts(array $parts): string
    {
        return implode(' ', array_filter(array_map('trim', $parts), fn($part) => $part !== ''));
    }

    /**
     * DEFAULT clause of a column ('' when no default is defined)
     * @param Column $column
     * @return string
     */
    protected function defaultClause(Column $column): string
    {
        if (!$column->isDefaultDefined()) {
            return '';
        }

        $default = $column->getDefault();

        if (is_null($default)) {
            return 'DEFAULT NULL';
        } elseif ($default instanceof ColumnDefault) {
            return 'DEFAULT ' . $default->value;
        } elseif (is_string($default)) {
            return 'DEFAULT ' . $this->quoteDefaultString($default);
        }

        return 'DEFAULT ' . $default;
    }

    /**
     * Quote a string literal used as a column default
     * @param string $value
     * @return string
     */
    protected function quoteDefaultString(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

}
