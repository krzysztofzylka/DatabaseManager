<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Exception\ConditionException;
use krzysztofzylka\DatabaseManager\Exception\SelectException;
use krzysztofzylka\DatabaseManager\Exception\TableException;
use krzysztofzylka\DatabaseManager\Helper\Where;
use krzysztofzylka\DatabaseManager\Table;
use Exception;
use PDO;

trait TableSelect {

    /**
     * Find one element
     * @param array|null $condition
     * @param ?string $orderBy
     * @return array
     * @throws ConditionException
     * @throws SelectException
     * @throws TableException
     */
    public function find(null|array $condition = null, ?string $orderBy = null) : array {
        $sql = 'SELECT ' . $this->prepareColumnListForSql() . ' FROM `' . $this->getName() . '` ' . implode(', ', $this->prepareBindData());

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . (new Where())->getPrepareConditions($condition);
        }

        if (!is_null($orderBy)) {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        DatabaseManager::setLastSql($sql);

        try {
            $pdo = $this->pdo->prepare($sql);
            $pdo->execute();
            $fetchData = $pdo->fetch(PDO::FETCH_ASSOC);

            return $this->prepareReturnValue($fetchData);
        } catch (Exception $exception) {
            throw new SelectException($exception->getMessage());
        }
    }

    /**
     * Find all elements
     * @param array|null $condition
     * @param ?string $orderBy
     * @param ?string $limit
     * @param ?string $groupBy
     * @return array
     * @throws SelectException
     */
    public function findAll(null|array $condition = null, ?string $orderBy = null, ?string $limit = null, ?string $groupBy = null) : array {
        $sql = 'SELECT ' . $this->prepareColumnListForSql() . ' FROM `' . $this->getName() . '` ' . implode(', ', $this->prepareBindData());

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . (new Where())->getPrepareConditions($condition);
        }

        if (!is_null($groupBy)) {
            $sql .= ' GROUP BY ' . $groupBy;
        }

        if (!is_null($orderBy)) {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        if (!is_null($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }

        DatabaseManager::setLastSql($sql);

        try {
            $pdo = $this->pdo->prepare($sql);
            $pdo->execute();
            $fetchData = $pdo->fetchAll(PDO::FETCH_ASSOC);

            return $this->prepareReturnValue($fetchData);
        } catch (Exception $exception) {
            throw new SelectException($exception->getMessage());
        }
    }

    /**
     * Count
     * @param ?array $condition
     * @param ?string $groupBy
     * @return int
     * @throws ConditionException
     * @throws SelectException
     */
    public function findCount(?array $condition = null, ?string $groupBy = null) : int {
        $sql = 'SELECT COUNT(*) as `count` FROM `' . $this->getName() . '`';

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . (new Where())->getPrepareConditions($condition);
        }

        if (!is_null($groupBy)) {
            $sql .= ' GROUP BY ' . $groupBy;
        }

        DatabaseManager::setLastSql($sql);

        try {
            $count = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

            return $count['count'] ?? 0;
        } catch (Exception $e) {
            throw new SelectException($e->getMessage());
        }
    }

    /**
     * Isset
     * @param ?array $condition
     * @return bool
     * @throws ConditionException
     * @throws SelectException
     */
    public function findIsset(?array $condition = null) : bool {
        return $this->findCount($condition) > 0;
    }

    /**
     * Prepare column list for select
     * @return string
     * @throws TableException
     */
    private function prepareColumnListForSql() : string {
        $columnList = $this->prepareColumnList(false);

        if (isset($this->bind)) {
            foreach ($this->bind as $bind) {
                $bindTable = (new Table())->setName($bind['tableName']);
                $columnList = array_merge($columnList, $bindTable->prepareColumnList(false));
            }
        }

        return implode(', ', $columnList);
    }

}