<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use Exception;
use krzysztofzylka\DatabaseManager\ConnectionManager;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Helper\SqlBuilder;
use krzysztofzylka\DatabaseManager\Helper\Where;
use krzysztofzylka\DatabaseManager\Table;
use PDO;

trait TableSelect
{

    /**
     * Get database type for the current connection
     * @return DatabaseType
     */
    private function getDatabaseType(): DatabaseType
    {
        if (isset($this->databaseType)) {
            return $this->databaseType;
        }

        if (isset($this->connectionName) && !is_null($this->connectionName)) {
            try {
                return ConnectionManager::getConnection($this->connectionName)->getType();
            } catch (Exception $e) {
            }
        }

        return DatabaseManager::$connection->getType();
    }

    /**
     * Get SQL identifier quote character
     * @return string
     */
    private function getIdQuote(): string
    {
        if ($this->getDatabaseType() === DatabaseType::postgres) {
            return '"';
        }
        return '`';
    }

    /**
     * Find one element
     * @param array|null $condition
     * @param array|null $columns
     * @param ?string $orderBy
     * @return array
     * @throws DatabaseManagerException
     */
    public function find(?array $condition = null, ?array $columns = null, ?string $orderBy = null): array
    {
        $whereHelper = new Where();
        $whereHelper->setDatabaseType($this->getDatabaseType());

        $whereData = null;
        $bindValues = [];

        if ($condition) {
            $whereData = $whereHelper->getPrepareConditions($condition);
            $bindValues = $whereData['bind'];
            $whereData = $whereData['sql'];
        }

        $bindData = $this->prepareBindData();
        $joinSql = implode(' ', $bindData['sql']);
        $bindValues = array_merge($bindValues, $bindData['bind']);

        $sql = SqlBuilder::select(
            $columns ? $this->prepareCustomColumnList($columns) : $this->prepareColumnListForSql(),
            $this->getName(),
            trim($joinSql),
            $whereData,
            null,
            $orderBy,
            1,
            $this->getDatabaseType()
        );

        DatabaseManager::setLastSql($sql);

        try {
            $pdo = $this->pdo->prepare($sql);

            foreach ($bindValues as $key => $value) {
                $pdo->bindValue($key, $value);
            }

            $pdo->execute();
            $fetchData = $pdo->fetch(PDO::FETCH_ASSOC);

            return $this->prepareReturnValue($fetchData);
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

    /**
     * Find all elements
     * @param array|null $condition
     * @param array|null $columns
     * @param ?string $orderBy
     * @param ?string $limit
     * @param ?string $groupBy
     * @return array
     * @throws DatabaseManagerException
     */
    public function findAll(?array $condition = null, ?array $columns = null, ?string $orderBy = null, ?string $limit = null, ?string $groupBy = null): array
    {
        $whereHelper = new Where();
        $whereHelper->setDatabaseType($this->getDatabaseType());

        $whereData = null;
        $bindValues = [];
        $bindIndex = 0; // Reset bind index

        if ($condition) {
            $whereData = $whereHelper->getPrepareConditions($condition, 'AND', $bindIndex);
            $bindValues = $whereData['bind'];
            $whereData = $whereData['sql'];
        }

        $bindData = $this->prepareBindData($bindIndex);
        $joinSql = implode(' ', $bindData['sql']);
        $bindValues = array_merge($bindValues, $bindData['bind']);

        $sql = SqlBuilder::select(
            $columns ? $this->prepareCustomColumnList($columns) : $this->prepareColumnListForSql(),
            $this->getName(),
            trim($joinSql),
            $whereData,
            $groupBy ? $this->addBackticksToColumns($groupBy) : null,
            $orderBy ? $this->addBackticksToColumns($orderBy) : null,
            $limit,
            $this->getDatabaseType()
        );

        DatabaseManager::setLastSql($sql);

        try {
            $pdo = $this->pdo->prepare($sql);

            foreach ($bindValues as $key => $value) {
                $pdo->bindValue($key, $value);
            }

            $pdo->execute();
            $fetchData = $pdo->fetchAll(PDO::FETCH_ASSOC);

            return $this->prepareReturnValue($fetchData);
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

    /**
     * Count
     * @param ?array $condition
     * @param ?string $groupBy
     * @return int
     * @throws DatabaseManagerException
     */
    public function findCount(?array $condition = null, ?string $groupBy = null): int
    {
        $whereHelper = new Where();
        $whereHelper->setDatabaseType($this->getDatabaseType());

        $whereData = null;
        $bindValues = [];
        $bindIndex = 0; // Reset bind index

        if ($condition) {
            $whereData = $whereHelper->getPrepareConditions($condition, 'AND', $bindIndex);
            $bindValues = $whereData['bind'];
            $whereData = $whereData['sql'];
        }

        $bindData = $this->prepareBindData($bindIndex);
        $joinSql = implode(' ', $bindData['sql']);
        $bindValues = array_merge($bindValues, $bindData['bind']);

        $sql = SqlBuilder::select(
            'COUNT(*) as `count`',
            $this->getName(),
            trim($joinSql),
            $whereData,
            $groupBy,
            null,
            null,
            $this->getDatabaseType()
        );

        DatabaseManager::setLastSql($sql);

        try {
            $pdo = $this->pdo->prepare($sql);

            foreach ($bindValues as $key => $value) {
                $pdo->bindValue($key, $value);
            }

            $pdo->execute();
            $count = $pdo->fetch(PDO::FETCH_ASSOC);

            return $count['count'] ?? 0;
        } catch (Exception $e) {
            throw new DatabaseManagerException($e->getMessage());
        }
    }

    /**
     * Isset
     * @param ?array $condition
     * @return bool
     * @throws DatabaseManagerException
     */
    public function findIsset(?array $condition = null): bool
    {
        return $this->findCount($condition) > 0;
    }

    /**
     * Prepare column list for select
     * @return string
     * @throws DatabaseManagerException
     */
    private function prepareColumnListForSql(): string
    {
        $columnList = $this->prepareColumnList(false);

        if (isset($this->bind)) {
            foreach ($this->bind as $bind) {
                // Użyj oryginalnej nazwy tabeli bez aliasu
                $tableName = $bind['tableName'];
                if (str_contains($tableName, '` as `')) {
                    $tableName = explode('` as `', $tableName)[0];
                }

                $bindTable = (new Table($tableName, $this->connectionName));
                $columnList = array_merge($columnList, $bindTable->prepareColumnList(false, $bind['tableAlias']));
            }
        }

        return implode(', ', $columnList);
    }

    /**
     * Prepare custom column list
     * @param array $columns
     * @return string
     */
    public function prepareCustomColumnList(array $columns): string
    {
        $quote = $this->getIdQuote();

        foreach ($columns as $id => $column) {
            if (!str_contains($column, '.')) {
                $column = $this->getName() . '.' . $column;
            }

            $columns[$id] = \krzysztofzylka\DatabaseManager\Helper\Table::prepareColumnNameWithAlias($column, $quote) . ' as ' . $quote . $column . $quote;
        }

        return implode(', ', $columns);
    }

    /**
     * Add backticks to column names in GROUP BY and ORDER BY
     * @param string $columns
     * @return string
     */
    private function addBackticksToColumns(string $columns): string
    {
        $quote = $this->getIdQuote();

        $functions = [];
        $functionCounter = 0;

        $processedColumns = preg_replace_callback(
            '/[A-Za-z_][A-Za-z0-9_]*\s*\([^)]*\)/',
            function($matches) use (&$functions, &$functionCounter) {
                $placeholder = "___FUNCTION_{$functionCounter}___";
                $functions[$placeholder] = $matches[0];
                $functionCounter++;
                return $placeholder;
            },
            $columns
        );

        $parts = explode(',', $processedColumns);
        $result = [];

        foreach ($parts as $part) {
            $part = trim($part);

            // Sprawdź czy ma kierunek sortowania
            $direction = '';
            if (preg_match('/\s+(ASC|DESC)$/i', $part, $matches)) {
                $direction = ' ' . strtoupper($matches[1]);
                $part = trim(str_replace($matches[0], '', $part));
            }

            if (preg_match('/___FUNCTION_(\d+)___/', $part, $matches)) {
                $functionIndex = $matches[1];
                $functionPlaceholder = "___FUNCTION_{$functionIndex}___";

                if (isset($functions[$functionPlaceholder])) {
                    $function = $functions[$functionPlaceholder];

                    // Przetwórz funkcję - dodaj backticks do nazw kolumn w funkcji
                    $processedFunction = $this->processFunctionInOrderBy($function, $quote);
                    $result[] = $processedFunction . $direction;
                }
            } elseif (str_contains($part, '.')) {
                // Kolumna z aliasem tabeli
                $tableColumn = explode('.', $part, 2);
                $table = trim($tableColumn[0]);
                $column = trim($tableColumn[1]);

                // Usuń backticks jeśli istnieją
                $table = str_replace(['`', '"'], '', $table);
                $column = str_replace(['`', '"'], '', $column);

                $result[] = $quote . $table . $quote . '.' . $quote . $column . $quote . $direction;
            } else {
                // Zwykła kolumna lub wartość
                $column = str_replace(['`', '"'], '', $part);

                // Sprawdź czy to string literal (w cudzysłowach)
                if ((str_starts_with($column, "'") && str_ends_with($column, "'")) ||
                    (str_starts_with($column, '"') && str_ends_with($column, '"'))) {
                    $result[] = $column . $direction;
                } else {
                    $result[] = $quote . $column . $quote . $direction;
                }
            }
        }

        return implode(', ', $result);
    }

    /**
     * Process function in ORDER BY clause
     * @param string $function
     * @param string $quote
     * @return string
     */
    private function processFunctionInOrderBy(string $function, string $quote): string
    {
        // Znajdź nazwę funkcji i jej parametry
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*\((.*)\)$/', $function, $matches)) {
            $functionName = $matches[1];
            $parameters = $matches[2];

            // Przetwórz parametry funkcji
            $processedParams = [];
            $paramParts = $this->splitFunctionParameters($parameters);

            foreach ($paramParts as $param) {
                $param = trim($param);

                if (str_contains($param, '.')) {
                    // Kolumna z aliasem tabeli
                    $tableColumn = explode('.', $param, 2);
                    $table = trim($tableColumn[0]);
                    $column = trim($tableColumn[1]);

                    // Usuń backticks jeśli istnieją
                    $table = str_replace(['`', '"'], '', $table);
                    $column = str_replace(['`', '"'], '', $column);

                    $processedParams[] = $quote . $table . $quote . '.' . $quote . $column . $quote;
                } elseif ((str_starts_with($param, "'") && str_ends_with($param, "'")) ||
                         (str_starts_with($param, '"') && str_ends_with($param, '"'))) {
                    // String literal
                    $processedParams[] = $param;
                } else {
                    // Zwykła kolumna
                    $column = str_replace(['`', '"'], '', $param);
                    $processedParams[] = $quote . $column . $quote;
                }
            }

            return $functionName . '(' . implode(', ', $processedParams) . ')';
        }

        return $function;
    }

    /**
     * Split function parameters correctly
     * @param string $parameters
     * @return array
     */
    private function splitFunctionParameters(string $parameters): array
    {
        $result = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $bracketCount = 0;

        for ($i = 0; $i < strlen($parameters); $i++) {
            $char = $parameters[$i];

            if ($char === "'" || $char === '"') {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            }

            if (!$inString) {
                if ($char === '(') {
                    $bracketCount++;
                } elseif ($char === ')') {
                    $bracketCount--;
                } elseif ($char === ',' && $bracketCount === 0) {
                    $result[] = trim($current);
                    $current = '';
                    continue;
                }
            }

            $current .= $char;
        }

        if (!empty(trim($current))) {
            $result[] = trim($current);
        }

        return $result;
    }
}