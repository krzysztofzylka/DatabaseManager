<?php

namespace DatabaseManager;

use DatabaseManager\Enum\BindType;
use DatabaseManager\Enum\DatabaseType;
use DatabaseManager\Exception\DeleteException;
use DatabaseManager\Exception\InsertException;
use DatabaseManager\Exception\UpdateException;
use DatabaseManager\Trait\TableHelpers;
use DatabaseManager\Trait\TableSelect;
use Exception;
use PDO;

class GetTable {

    private string $name;
    private PDO $pdo;
    private array $bind;
    private ?int $id = null;
    private ?string $lastSql = null;

    use TableSelect;
    use TableHelpers;

    /**
     * Constructor
     */
    public function __construct() {
        $this->pdo = DatabaseManager::$connection->getConnection();
    }

    /**
     * Get table name
     * @return string
     */
    private function getName() : string {
        return $this->name;
    }

    /**
     * Set column name
     * @param string $name
     * @return GetTable
     */
    public function setName(string $name) : self {
        $this->name = htmlspecialchars($name);

        return $this;
    }

    /**
     * Get ID
     * @return ?int
     */
    public function getId() : ?int {
        return $this->id;
    }

    /**
     * Set ID
     * @param ?int $id
     * @return GetTable
     */
    public function setId(?int $id) : self {
        $this->id = $id;

        return $this;
    }

    /**
     * Get last sql
     * @return string|null
     */
    public function getLastSql() : ?string {
        return $this->lastSql;
    }

    /**
     * Set last sql
     * @param ?string $lastSql
     */
    public function setLastSql(?string $lastSql) : void {
        $this->lastSql = $lastSql;
    }

    /**
     * Bind table
     * @param BindType $bindType
     * @param string $tableName
     * @param ?string $primaryKey
     * @param ?string $foreignKey
     * @return $this
     */
    public function bind(BindType $bindType, string $tableName, ?string $primaryKey = null, ?string $foreignKey = null) : self {
        $primaryKey = $primaryKey ?? ('`' . $this->getName() . '`.`id`');
        $foreignKey = $foreignKey ?? ('`' . $this->getName() . '`.`' . $this->getName() . '_id`');

        $this->bind[] = ['type' => $bindType->value, 'tableName' => $tableName, 'primaryKey' => $primaryKey, 'foreignKey' => $foreignKey];

        return $this;
    }

    /**
     * Get column list
     * @return array|bool
     */
    public function columnList() : array|bool {
        if (DatabaseManager::getDatabaseType() === DatabaseType::sqlite) {
            $return = [];
            $sql = 'pragma table_info("user");';
            $data = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            foreach ($data as $column) {
                $return[] = [
                    'field' => $column['name'],
                    'type' => $column['type'],
                    'null' => $column['notnull'] ? 'NO' : 'YES',
                    'key' => $column['pk'] ? 'PRI' : '',
                    'default' => $column['dflt_value'],
                    'extra' => ''
                ];
            }

            $this->lastSql = $sql;
            return $return;
        } elseif (DatabaseManager::getDatabaseType() === DatabaseType::mysql) {
            $sql = 'DESCRIBE `' . $this->getName() . '`;';
            $this->lastSql = $sql;
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        return false;
    }

    /**
     * prepare column list
     * @param bool $asString
     * @return array|string
     */
    public function prepareColumnList(bool $asString = true) : array|string {
        $columnList = $this->columnList();
        $columnListString = [];

        foreach ($columnList as $column) {
            $columnListString[] = '`' . $this->getName() . '`.`' . $column['field'] . '` as `' . $this->getName() . '.' . $column['field'] . '`';
        }

        if ($asString) {
            return implode(', ', $columnListString);
        }

        return $columnListString;
    }

    /**
     * Insert data
     * @param array $data
     * @return bool
     * @throws InsertException
     */
    public function insert(array $data) : bool {
        try {
            $sql = 'INSERT INTO ' . $this->getName() . ' (`' . implode('`, `', array_keys($data)) . '`) VALUES (:' . implode(', :', array_keys($data)) . ')';
            $insert = $this->pdo->prepare($sql);

            foreach ($data as $name => $value) {
                $insert->bindValue(':' . $name, $value);
            }

            if ($insert->execute()) {
                $this->lastSql = $sql;

                $this->setId($this->pdo->lastInsertId());

                return true;
            } else {
                $this->setId(null);

                return false;
            }
        } catch (Exception $exception) {
            throw new InsertException($exception->getMessage());
        }
    }

    /**
     * Update
     * @param array $data
     * @return bool
     * @throws UpdateException
     */
    public function update(array $data) : bool {
        if (is_null($this->getName())) {
            throw new UpdateException('ID is not defined');
        }

        $set = [];

        foreach (array_keys($data) as $name) {
            $set[] = '`' . $name . '` = :' . $name;
        }

        $sql = 'UPDATE `' . $this->getName() . '` SET ' . implode(', ', $set) . ' WHERE id=' . $this->getId();

        $update = $this->pdo->prepare($sql);

        foreach ($data as $name => $value) {
            $update->bindValue(':' . $name, $value);
        }

        if ($update->execute()) {
            $this->lastSql = $sql;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete
     * @param ?int $id
     * @return bool
     * @throws DeleteException
     */
    public function delete(?int $id = null) : bool {
        $id = $id ?? $this->getId();

        if (is_null($id)) {
            throw new DeleteException('ID must be integer');
        }

        $sql = 'DELETE FROM `' . $this->getName() . '` WHERE id=' . ($id ?? $this->getId());

        try {
            $this->lastSql = $sql;

            return (bool)$this->pdo->exec($sql);
        } catch (Exception $e) {
            throw new DeleteException($e->getMessage());
        }
    }

}