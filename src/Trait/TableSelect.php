<?php

namespace DatabaseManager\Trait;

use DatabaseManager\Condition;
use DatabaseManager\Exception\CountException;
use DatabaseManager\GetTable;
use Exception;
use PDO;

trait TableSelect {

    /**
     * Find one element
     * @param ?Condition $condition
     * @return array
     */
    public function find(?Condition $condition = null) : array {
        $columnList = $this->prepareColumnList(false);

        if (isset($this->bind)) {
            foreach ($this->bind as $bind) {
                $bindTable = (new GetTable())->setName($bind['tableName']);
                $columnList = array_merge($columnList, $bindTable->prepareColumnList(false));
            }
        }

        $sql = 'SELECT ' . implode(', ', $columnList) . ' FROM `' . $this->getName() . '` ' . implode(', ', $this->prepareBindData());

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . $condition->getPrepareConditions();
        }

        $this->lastSql = $sql;
        $pdo = $this->pdo->prepare($sql);
        $pdo->execute();
        $fetchData = $pdo->fetch(PDO::FETCH_ASSOC);

        return $this->prepareReturnValue($fetchData);
    }

    /**
     * Find all elements
     * @param ?Condition $condition
     * @return array
     */
    public function findAll(?Condition $condition = null) : array {
        $columnList = $this->prepareColumnList(false);

        if (isset($this->bind)) {
            foreach ($this->bind as $bind) {
                $bindTable = (new GetTable())->setName($bind['tableName']);
                $columnList = array_merge($columnList, $bindTable->prepareColumnList(false));
            }
        }

        $sql = 'SELECT ' . implode(', ', $columnList) . ' FROM `' . $this->getName() . '` ' . implode(', ', $this->prepareBindData());

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . $condition->getPrepareConditions();
        }

        $this->lastSql = $sql;
        $pdo = $this->pdo->prepare($sql);
        $pdo->execute();
        $fetchData = $pdo->fetchAll(PDO::FETCH_ASSOC);

        return $this->prepareReturnValue($fetchData);
    }

    /**
     * Count
     * @param ?Condition $condition
     * @return int
     * @throws CountException
     */
    public function findCount(?Condition $condition = null) : int {
        $sql = 'SELECT COUNT(*) as `count` FROM `' . $this->getName() . '`';

        if (!is_null($condition)) {
            $sql .= ' WHERE ' . $condition->getPrepareConditions();
        }

        try {
            $this->lastSql = $sql;
            $count = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

            return $count['count'] ?? 0;
        } catch (Exception $e) {
            throw new CountException($e->getMessage());
        }
    }

}