<?php

namespace DatabaseManager\PublicExtends;

use DatabaseManager\Condition;
use DatabaseManager\Exception\DatabaseManagerException;
use DatabaseManager\Exception\SelectException;
use DatabaseManager\Exception\UpdateException;
use DatabaseManager\GetTable;

class Model {

    private GetTable $database;
    public string $databaseTable;
    public bool $useDatabase = true;
    public int $id;

    /**
     * Prepare database
     * @throws DatabaseManagerException
     */
    public function __construct() {
        if ($this->useDatabase) {
            if (!isset($this->databaseTable)) {
                throw new DatabaseManagerException('Please set table name');
            }
        }

        $this->database = (new GetTable())->setName($this->databaseTable);
    }

    /**
     * Find one element
     * @param ?Condition $condition
     * @return array
     * @throws SelectException
     */
    public function find(?Condition $condition = null) : array {
        return $this->database->find($condition);
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
        return $this->database->findAll($condition, $orderBy, $limit);
    }

    /**
     * Count
     * @param ?Condition $condition
     * @return int
     * @throws SelectException
     */
    public function findCount(?Condition $condition = null) : int {
        return $this->database->findCount($condition);
    }

    /**
     * Update
     * @param array $data
     * @return bool
     * @throws UpdateException
     */
    public function update(array $data) : bool {
        $this->database->update($data);
    }
}