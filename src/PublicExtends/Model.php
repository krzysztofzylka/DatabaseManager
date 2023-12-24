<?php

namespace krzysztofzylka\DatabaseManager\PublicExtends;

use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Table;

class Model
{

    private Table $database;

    public string $databaseTable;

    public bool $useDatabase = true;

    /**
     * Get ID
     * @return int
     */
    public function getId(): int
    {
        return $this->database->getId();
    }

    /**
     * Set ID
     * @param ?int $id
     * @return int
     */
    public function setId(?int $id = null): int
    {
        return $this->database->setId($id);
    }

    /**
     * Prepare database
     * @throws DatabaseManagerException
     */
    public function __construct()
    {
        if ($this->useDatabase) {
            if (!isset($this->databaseTable)) {
                throw new DatabaseManagerException('Please set table name');
            }
        }

        $this->database = (new Table())->setName($this->databaseTable);
    }

    /**
     * Find one element
     * @param ?Condition $condition
     * @return array
     * @throws DatabaseManagerException
     */
    public function find(?Condition $condition = null): array
    {
        return $this->database->find($condition);
    }

    /**
     * Find all elements
     * @param ?Condition $condition
     * @param ?string $orderBy
     * @param ?string $limit
     * @return array
     * @throws DatabaseManagerException
     */
    public function findAll(?Condition $condition = null, ?string $orderBy = null, ?string $limit = null): array
    {
        return $this->database->findAll($condition, $orderBy, $limit);
    }

    /**
     * Count
     * @param ?Condition $condition
     * @return int
     * @throws DatabaseManagerException
     */
    public function findCount(?Condition $condition = null): int
    {
        return $this->database->findCount($condition);
    }

    /**
     * Update
     * @param array $data
     * @return bool
     * @throws DatabaseManagerException
     */
    public function update(array $data): bool
    {
        return $this->database->update($data);
    }

    /**
     * Insert data
     * @param array $data
     * @return bool
     * @throws DatabaseManagerException
     */
    public function insert(array $data): bool
    {
        return $this->database->insert($data);
    }

    /**
     * Delete
     * @param ?int $id
     * @return bool
     * @throws DatabaseManagerException
     */
    public function delete(?int $id = null): bool
    {
        return $this->database->delete($id);
    }

    /**
     * Bind table
     * @param BindType $bindType
     * @param string $tableName
     * @param ?string $primaryKey
     * @param ?string $foreignKey
     * @return Model
     */
    public function bind(BindType $bindType, string $tableName, ?string $primaryKey = null, ?string $foreignKey = null): self
    {
        $this->database->bind($bindType, $tableName, $primaryKey, $foreignKey);

        return $this;
    }
}