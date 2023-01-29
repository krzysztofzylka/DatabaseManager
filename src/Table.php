<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DeleteException;
use krzysztofzylka\DatabaseManager\Exception\InsertException;
use krzysztofzylka\DatabaseManager\Exception\TableException;
use krzysztofzylka\DatabaseManager\Trait\TableHelpers;
use krzysztofzylka\DatabaseManager\Trait\TableSelect;
use krzysztofzylka\DatabaseManager\Trait\TableUpdate;
use Exception;
use PDO;

class Table {

    /**
     * Table name
     * @var string
     */
    private string $name;

    /**
     * PDO connection
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Bind list
     * @var array
     */
    private array $bind;

    /**
     * Element id
     * @var ?int
     */
    private ?int $id = null;

    use TableSelect;
    use TableHelpers;
    use TableUpdate;

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
     * @return Table
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
     * @return Table
     */
    public function setId(?int $id = null) : self {
        $this->id = $id;

        return $this;
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
     * @param ?string $columnName column name
     * @return array|bool
     * @throws TableException
     */
    public function columnList(?string $columnName = null) : array|bool {
        try {
            $return = [];

            if (DatabaseManager::getDatabaseType() === DatabaseType::sqlite) {
                $sql = 'pragma table_info("user");';
                $data = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

                foreach ($data as $column) {
                    $return[] = [
                        'Field' => $column['name'],
                        'Type' => $column['type'],
                        'Null' => $column['notnull'] ? 'NO' : 'YES',
                        'Key' => $column['pk'] ? 'PRI' : '',
                        'Default' => $column['dflt_value'],
                        'Extra' => ''
                    ];
                }

                DatabaseManager::setLastSql($sql);
            } elseif (DatabaseManager::getDatabaseType() === DatabaseType::mysql) {
                $sql = 'DESCRIBE `' . $this->getName() . '`;';
                DatabaseManager::setLastSql($sql);
                $return = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            }

            if (!is_null($columnName)) {
                foreach ($return as $data) {
                    if ($data['Field'] === $columnName) {
                        return $data;
                    }
                }
            }

            return $return ?? false;
        } catch (Exception $exception) {
            throw new TableException($exception->getMessage());
        }
    }

    /**
     * prepare column list
     * @param bool $asString
     * @return array|string
     * @throws TableException
     */
    public function prepareColumnList(bool $asString = true) : array|string {
        $columnList = $this->columnList();
        $columnListString = [];

        foreach ($columnList as $column) {
            $columnListString[] = '`' . $this->getName() . '`.`' . $column['Field'] . '` as `' . $this->getName() . '.' . $column['Field'] . '`';
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
            $sql = 'INSERT INTO `' . $this->getName() . '` (`' . implode('`, `', array_keys($data)) . '`) VALUES (:' . implode(', :', array_keys($data)) . ')';
            DatabaseManager::setLastSql($sql);
            $insert = $this->pdo->prepare($sql);

            foreach ($data as $name => $value) {
                $insert->bindValue(':' . $name, $value);
            }

            if ($insert->execute()) {
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

        try {
            $sql = 'DELETE FROM `' . $this->getName() . '` WHERE id=' . ($id ?? $this->getId());
            DatabaseManager::setLastSql($sql);

            return (bool)$this->pdo->exec($sql);
        } catch (Exception $e) {
            throw new DeleteException($e->getMessage());
        }
    }

    /**
     * Table exists in database
     * @return bool
     */
    public function exists() : bool {
        $query = $this->pdo->query('SHOW TABLES LIKE "' . $this->getName() . '";');

        return !empty($query->fetchAll());
    }

}