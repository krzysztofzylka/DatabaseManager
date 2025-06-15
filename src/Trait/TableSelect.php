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

        $sql = SqlBuilder::select(
            $columns ? $this->prepareCustomColumnList($columns) : $this->prepareColumnListForSql(),
            $this->getName(),
            trim(implode(' ', $this->prepareBindData())),
            $condition ? $whereHelper->getPrepareConditions($condition) : null,
            null,
            $orderBy,
            1,
            $this->getDatabaseType()
        );

        DatabaseManager::setLastSql($sql);

        try {
            $pdo = $this->pdo->prepare($sql);
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

        $sql = SqlBuilder::select(
            $columns ? $this->prepareCustomColumnList($columns) : $this->prepareColumnListForSql(),
            $this->getName(),
            trim(implode(' ', $this->prepareBindData())),
            $condition ? $whereHelper->getPrepareConditions($condition) : null,
            $groupBy,
            $orderBy,
            $limit,
            $this->getDatabaseType()
        );

        DatabaseManager::setLastSql($sql);

        try {
            $pdo = $this->pdo->prepare($sql);
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

        $sql = SqlBuilder::select(
            'COUNT(*) as `count`',
            $this->getName(),
            trim(implode(' ', $this->prepareBindData())),
            $condition ? $whereHelper->getPrepareConditions($condition) : null,
            $groupBy,
            null,
            null,
            $this->getDatabaseType()
        );

        DatabaseManager::setLastSql($sql);

        try {
            $count = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

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
                $bindTable = (new Table($bind['tableName'], $this->connectionName));
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

}