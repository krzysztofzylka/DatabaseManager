<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Helper\SqlBuilder;
use krzysztofzylka\DatabaseManager\Helper\Where;
use krzysztofzylka\DatabaseManager\Table;
use Exception;
use PDO;

trait TableSelect
{

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
        $sql = SqlBuilder::select(
            $columns ? $this->prepareCustomColumnList($columns) : $this->prepareColumnListForSql(),
            $this->getName(),
            trim(implode(' ', $this->prepareBindData())),
            $condition ? (new Where())->getPrepareConditions($condition) : null,
            null,
            $orderBy
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
        $sql = SqlBuilder::select(
            $columns ? $this->prepareCustomColumnList($columns) : $this->prepareColumnListForSql(),
            $this->getName(),
            trim(implode(' ', $this->prepareBindData())),
            $condition ? (new Where())->getPrepareConditions($condition) : null,
            $groupBy,
            $orderBy,
            $limit
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
        $sql = SqlBuilder::select(
            'COUNT(*) as `count`',
            $this->getName(),
            trim(implode(' ', $this->prepareBindData())),
            $condition ? (new Where())->getPrepareConditions($condition) : null,
            $groupBy
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
     */
    private function prepareColumnListForSql(): string
    {
        $columnList = $this->prepareColumnList(false);

        if (isset($this->bind)) {
            foreach ($this->bind as $bind) {
                $bindTable = (new Table())->setName($bind['tableName']);
                $columnList = array_merge($columnList, $bindTable->prepareColumnList(false));
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
        foreach ($columns as $id => $column) {
            if (!str_contains($column, '.')) {
                $column = $this->getName() . '.' . $column;
            }

            $columns[$id] = \krzysztofzylka\DatabaseManager\Helper\Table::prepareColumnNameWithAlias($column) . ' as `' . $column . '`';
        }

        return implode(', ', $columns);
    }

}