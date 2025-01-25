<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Trait\TableHelpers;
use krzysztofzylka\DatabaseManager\Trait\TableSelect;
use krzysztofzylka\DatabaseManager\Trait\TableUpdate;
use PDO;

class Table
{

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
     * @param ?string $tableName Table name
     */
    public function __construct(?string $tableName = null)
    {
        $this->pdo = DatabaseManager::$connection->getConnection();

        if (!is_null($tableName)) {
            $this->setName($tableName);
        }
    }

    /**
     * Get table name
     * @return string
     */
    private function getName(): string
    {
        return $this->name;
    }

    /**
     * Set column name
     * @param string $name
     * @return Table
     */
    public function setName(string $name): self
    {
        $this->name = htmlspecialchars($name);

        return $this;
    }

    /**
     * Get ID
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set ID
     * @param ?int $id
     * @return Table
     */
    public function setId(?int $id = null): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Bind table
     * @param array|BindType $bind
     * @param string|null $tableName
     * @param ?string $primaryKey
     * @param ?string $foreignKey
     * @param array|Condition|null $condition
     * @return $this
     */
    public function bind(
        BindType|array $bind,
        ?string $tableName = null,
        ?string $primaryKey = null,
        ?string $foreignKey = null,
        null|array|Condition $condition = null
    ): self
    {
        if (is_array($bind)) {
            foreach ($bind as $key => $value) {
                if (is_string($value)) {
                    $explodeName = explode('.', $value);
                    $bindType = BindType::getFromName($explodeName[0]);
                    $this->bind($bindType, $explodeName[1]);
                } else {
                    $explodeName = explode('.', $key);
                    $bindType = BindType::getFromName($explodeName[0]);
                    $this->bind($bindType, $explodeName[1], $value['primaryKey'] ?? null, $value['foreignKey'] ?? null);
                }
            }

            return $this;
        }

        $primaryKey = $primaryKey ? Helper\Table::prepareColumnNameWithAlias($primaryKey) : ('`' . $this->getName() . '`.`id`');
        $foreignKey = $foreignKey ? Helper\Table::prepareColumnNameWithAlias($foreignKey) : ('`' . $tableName . '`.`' . $this->getName() . '_id`');

        $bindTableNames = array_column($this->bind ?? [], 'tableName');
        $bindSearch = array_search($tableName, $bindTableNames);

        if ($bindSearch !== false) {
            unset($this->bind[$bindSearch]);
        }

        $this->bind[] = [
            'type' => $bind->value,
            'tableName' => $tableName,
            'primaryKey' => $primaryKey,
            'foreignKey' => $foreignKey,
            'condition' => $condition
        ];

        return $this;
    }

    /**
     * Unbind table
     * @param string $tableName
     * @return $this
     */
    public function unbind(string $tableName): self
    {
        $search = array_search($tableName, array_column($this->bind, 'tableName'));

        if ($search !== false) {
            unset($this->bind[$search]);
        }

        return $this;
    }

    /**
     * Unbind all table
     * @return $this
     */
    public function unbindAll(): self
    {
        $this->bind = [];

        return $this;
    }

    /**
     * Get column list
     * @param ?string $columnName column name
     * @return array|bool
     * @throws DatabaseManagerException
     */
    public function columnList(?string $columnName = null): array|bool
    {
        try {
            $return = [];
            $cacheData = Cache::getData('columnList_' . $this->getName());

            if (!is_null($cacheData)) {
                $return = $cacheData;
            } else {
                if (DatabaseManager::getDatabaseType() === DatabaseType::sqlite) {
                    $sql = 'pragma table_info("user");';
                    DatabaseManager::setLastSql($sql);
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
                } elseif (DatabaseManager::getDatabaseType() === DatabaseType::mysql) {
                    $sql = 'DESCRIBE `' . $this->getName() . '`;';
                    DatabaseManager::setLastSql($sql);
                    $return = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            if (!is_null($columnName)) {
                foreach ($return as $data) {
                    if ($data['Field'] === $columnName) {
                        return $data;
                    }
                }
            }

            Cache::saveData('columnList_' . $this->getName(), $return);

            return $return ?? false;
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

    /**
     * prepare column list
     * @param bool $asString
     * @return array|string
     * @throws TableException
     */
    public function prepareColumnList(bool $asString = true): array|string
    {
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
     * @throws DatabaseManagerException
     */
    public function insert(array $data): bool
    {
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
                $this->setId();

                return false;
            }
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

    /**
     * Delete
     * @param ?int $id
     * @return bool
     * @throws DatabaseManagerException
     */
    public function delete(?int $id = null): bool
    {
        $id = $id ?? $this->getId();

        if (is_null($id)) {
            throw new DatabaseManagerException('ID must be integer');
        }

        try {
            $sql = 'DELETE FROM `' . $this->getName() . '` WHERE id=' . ($id ?? $this->getId());
            DatabaseManager::setLastSql($sql);

            return (bool)$this->pdo->exec($sql);
        } catch (Exception $e) {
            throw new DatabaseManagerException($e->getMessage());
        }
    }

    /**
     * Table exists in database
     * @return bool
     */
    public function exists(): bool
    {
        if (DatabaseManager::$connection->getType() === DatabaseType::sqlite) {
            $sql = 'SELECT sql FROM sqlite_master WHERE type="table" AND name LIKE "%' . $this->getName() . '%";';
            DatabaseManager::setLastSql($sql);

            return !empty($sql->fetchAll());
        } else {
            $sql = 'SELECT 1 FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1;';
            DatabaseManager::setLastSql($sql);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->getName()]);

            return $stmt->fetchColumn() >= 1;
        }
    }

    /**
     * Query
     * @param string $sql
     * @return array
     */
    public function query(string $sql): array
    {
        DatabaseManager::setLastSql($sql);

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

}