<?php

namespace DatabaseManager\Trait;

use DatabaseManager\Condition;
use DatabaseManager\Exception\SelectException;
use DatabaseManager\GetTable;
use Exception;
use PDO;

trait TableSelect {

    /**
     * Find one element
     * @param ?Condition $condition
     * @return array
     * @throws SelectException
     */
    public function find(?Condition $condition = null) : array {
        $sql = 'SELECT ' . $this->prepareColumnListForSql() . ' FROM `' . $this->getName() . '` ' . implode(', ', $this->prepareBindData());

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . $condition->getPrepareConditions();
        }

        try {
            $this->setLastSql($sql);
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
     * @param string|null $orderBy
     * @param string|null $limit
     * @return array
     * @throws SelectException
     */
    public function findAll(?Condition $condition = null, ?string $orderBy = null, ?string $limit = null) : array {
        $sql = 'SELECT ' . $this->prepareColumnListForSql() . ' FROM `' . $this->getName() . '` ' . implode(', ', $this->prepareBindData());

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . $condition->getPrepareConditions();
        }

        if (!is_null($orderBy)) {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        if (!is_null($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }

        try {
            $this->setLastSql($sql);
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
     * @return int
     * @throws SelectException
     */
    public function findCount(?Condition $condition = null) : int {
        $sql = 'SELECT COUNT(*) as `count` FROM `' . $this->getName() . '`';

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . $condition->getPrepareConditions();
        }

        try {
            $this->setLastSql($sql);
            $count = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

            return $count['count'] ?? 0;
        } catch (Exception $e) {
            throw new SelectException($e->getMessage());
        }
    }

    /**
     * Prepare column list for select
     * @return string
     */
    private function prepareColumnListForSql() : string {
        $columnList = $this->prepareColumnList(false);

        if (isset($this->bind)) {
            foreach ($this->bind as $bind) {
                $bindTable = (new GetTable())->setName($bind['tableName']);
                $columnList = array_merge($columnList, $bindTable->prepareColumnList(false));
            }
        }

        return implode(', ', $columnList);
    }

}