<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Exception\SelectException;
use krzysztofzylka\DatabaseManager\Table;
use Exception;
use PDO;

trait TableSelect {

    /**
     * Find one element
     * @param ?Condition $condition
     * @param ?string $orderBy
     * @return array
     * @throws SelectException
     */
    public function find(?Condition $condition = null, ?string $orderBy = null) : array {
        $sql = 'SELECT ' . $this->prepareColumnListForSql() . ' FROM `' . $this->getName() . '` ' . implode(', ', $this->prepareBindData());

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . $condition->getPrepareConditions();
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
     * @param ?Condition $condition
     * @param ?string $orderBy
     * @param ?string $limit
     * @param ?string $groupBy
     * @return array
     * @throws SelectException
     */
    public function findAll(?Condition $condition = null, ?string $orderBy = null, ?string $limit = null, ?string $groupBy = null) : array {
        $sql = 'SELECT ' . $this->prepareColumnListForSql() . ' FROM `' . $this->getName() . '` ' . implode(', ', $this->prepareBindData());

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . $condition->getPrepareConditions();
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
     * @param ?Condition $condition
     * @param ?string $groupBy
     * @return int
     * @throws SelectException
     */
    public function findCount(?Condition $condition = null, ?string $groupBy = null) : int {
        $sql = 'SELECT COUNT(*) as `count` FROM `' . $this->getName() . '`';

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . $condition->getPrepareConditions();
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
     * @param ?Condition $condition
     * @return bool
     * @throws SelectException
     */
    public function findIsset(?Condition $condition = null) : bool {
        return $this->findCount($condition) > 0;
    }

    /**
     * Prepare column list for select
     * @return string
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