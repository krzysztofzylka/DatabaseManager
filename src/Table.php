<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Helper\Where;
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

    /**
     * Connection name
     * @var string|null
     */
    private ?string $connectionName = null;

    /**
     * Database type
     * @var DatabaseType|null
     */
    private ?DatabaseType $databaseType = null;

    use TableSelect;
    use TableHelpers;
    use TableUpdate;

    /**
     * Constructor
     * @param ?string $tableName Table name
     * @param ?string $connectionName Connection name
     */
    public function __construct(?string $tableName = null, ?string $connectionName = null)
    {
        if (!is_null($connectionName)) {
            try {
                $this->connectionName = $connectionName;
                $connection = ConnectionManager::getConnection($connectionName);
                $this->pdo = $connection->getConnection();
                $this->databaseType = $connection->getType();
            } catch (Exception\ConnectException $e) {
                $this->pdo = DatabaseManager::$connection->getConnection();
                $this->connectionName = null;
                $this->databaseType = DatabaseManager::$connection->getType();
            }
        } else {
            $this->pdo = DatabaseManager::$connection->getConnection();
            $this->databaseType = DatabaseManager::$connection->getType();
        }

        if (!is_null($tableName)) {
            $this->setName($tableName);
        }
    }

    /**
     * Get table name
     * @return string
     */
    public function getName(): string
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
     * Get connection name
     * @return string|null
     */
    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    /**
     * Set connection by name
     * @param string $connectionName
     * @return $this
     * @throws Exception\DatabaseManagerException
     */
    public function setConnection(string $connectionName): self
    {
        try {
            $connection = ConnectionManager::getConnection($connectionName);
            $this->pdo = $connection->getConnection();
            $this->connectionName = $connectionName;
            $this->databaseType = $connection->getType();
        } catch (Exception\ConnectException $e) {
            throw new Exception\DatabaseManagerException("Connection '$connectionName' not found");
        }

        return $this;
    }

    /**
     * Reset to default connection
     * @return $this
     */
    public function useDefaultConnection(): self
    {
        $this->pdo = DatabaseManager::$connection->getConnection();
        $this->connectionName = null;
        $this->databaseType = DatabaseManager::$connection->getType();

        return $this;
    }

    /**
     * Get database type
     * @return DatabaseType
     */
    private function getDatabaseType(): DatabaseType
    {
        return $this->databaseType ?? DatabaseManager::$connection->getType();
    }

    /**
     * Get SQL identifier quote character
     * @return string
     */
    private function getIdQuote(): string
    {
        if ($this->getDatabaseType() === DatabaseType::postgres) {
            return '"';
        }
        return '`';
    }

    /**
     * Bind table
     * @param array|BindType $bind
     * @param string|null $tableName
     * @param ?string $primaryKey
     * @param ?string $foreignKey
     * @param array|Condition|null $condition
     * @param ?string $tableAlias
     * @return $this
     */
    public function bind(
        BindType|array $bind,
        ?string $tableName = null,
        ?string $primaryKey = null,
        ?string $foreignKey = null,
        null|array|Condition $condition = null,
        ?string $tableAlias = null
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

        $quote = $this->getIdQuote();
        $primaryKey = $primaryKey ? Helper\Table::prepareColumnNameWithAlias($primaryKey, $quote) : ($quote . $this->getName() . $quote . '.' . $quote . 'id' . $quote);
        $foreignKey = $foreignKey ? Helper\Table::prepareColumnNameWithAlias($foreignKey, $quote) : ($quote . $tableName . $quote . '.' . $quote . $this->getName() . '_id' . $quote);

        $bindTableNames = array_column($this->bind ?? [], 'tableName');
        $bindSearch = array_search($tableAlias ?? $tableName, $bindTableNames);

        if ($bindSearch !== false) {
            unset($this->bind[$bindSearch]);
        }

        $this->bind[] = [
            'type' => $bind->value,
            'tableName' => $tableName,
            'primaryKey' => $primaryKey,
            'foreignKey' => $foreignKey,
            'condition' => $condition,
            'tableAlias' => $tableAlias,
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
                if ($this->getDatabaseType() === DatabaseType::sqlite) {
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
                } elseif ($this->getDatabaseType() === DatabaseType::mysql) {
                    $sql = 'DESCRIBE `' . $this->getName() . '`;';
                    DatabaseManager::setLastSql($sql);
                    $return = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                } elseif ($this->getDatabaseType() === DatabaseType::postgres) {
                    $sql = "SELECT
                        column_name AS \"Field\",
                        data_type AS \"Type\",
                        CASE WHEN is_nullable = 'YES' THEN 'YES' ELSE 'NO' END AS \"Null\",
                        CASE WHEN column_default IS NOT NULL THEN column_default ELSE '' END AS \"Default\",
                        CASE WHEN column_name IN (SELECT column_name FROM information_schema.key_column_usage WHERE table_name = '" . $this->getName() . "') THEN 'PRI' ELSE '' END AS \"Key\",
                        '' AS \"Extra\"
                    FROM information_schema.columns
                    WHERE table_name = '" . $this->getName() . "'
                    ORDER BY ordinal_position;";
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
     * @param ?string $tableAlias
     * @return array|string
     * @throws DatabaseManagerException
     */
    public function prepareColumnList(bool $asString = true, ?string $tableAlias = null): array|string
    {
        $columnList = $this->columnList();
        $columnListString = [];
        $quote = $this->getIdQuote();

        foreach ($columnList as $column) {
            $columnListString[] = $quote . ($tableAlias ?? $this->getName()) . $quote . '.' . $quote . $column['Field'] . $quote . ' as ' . $quote . ($tableAlias ?? $this->getName()) . '.' . $column['Field'] . $quote;
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
            $quote = $this->getIdQuote();
            $sql = 'INSERT INTO ' . $quote . $this->getName() . $quote . ' (' .
                $quote . implode($quote . ', ' . $quote, array_keys($data)) . $quote .
                ') VALUES (:' . implode(', :', array_keys($data)) . ')';

            // Dla PostgreSQL, dodaj RETURNING id dla auto-generowanych kluczy
            if ($this->getDatabaseType() === DatabaseType::postgres) {
                $sql .= ' RETURNING id';
            }

            DatabaseManager::setLastSql($sql);
            $insert = $this->pdo->prepare($sql);

            foreach ($data as $name => $value) {
                $insert->bindValue(':' . $name, $value);
            }

            if ($insert->execute()) {
                if ($this->getDatabaseType() === DatabaseType::postgres) {
                    // Dla PostgreSQL, pobierz ID z zapytania RETURNING
                    $idResult = $insert->fetch(PDO::FETCH_ASSOC);
                    $this->setId($idResult['id'] ?? null);
                } else {
                    $this->setId($this->pdo->lastInsertId());
                }

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
            $quote = $this->getIdQuote();
            $sql = 'DELETE FROM ' . $quote . $this->getName() . $quote . ' WHERE id=' . ($id ?? $this->getId());
            DatabaseManager::setLastSql($sql);

            return (bool)$this->pdo->exec($sql);
        } catch (Exception $e) {
            throw new DatabaseManagerException($e->getMessage());
        }
    }

    /**
     * Delete by condition
     * @param array $condition
     * @return bool
     * @throws DatabaseManagerException
     */
    public function deleteByConditions(array $condition): bool
    {
        try {
            $quote = $this->getIdQuote();
            $whereHelper = new Where();
            $whereHelper->setDatabaseType($this->getDatabaseType());

            $sql = 'DELETE FROM ' . $quote . $this->getName() . $quote . ' WHERE ' . $whereHelper->getPrepareConditions($condition);
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
        if ($this->getDatabaseType() === DatabaseType::sqlite) {
            $sql = 'SELECT sql FROM sqlite_master WHERE type="table" AND name LIKE "%' . $this->getName() . '%";';
            DatabaseManager::setLastSql($sql);

            return !empty($sql->fetchAll());
        } elseif ($this->getDatabaseType() === DatabaseType::postgres) {
            $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ? LIMIT 1;";
            DatabaseManager::setLastSql($sql);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->getName()]);

            return $stmt->fetchColumn() >= 1;
        } else {
            $sql = 'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1;';
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

    /**
     * Get PDO instance
     * @return PDO
     */
    public function getPdoInstance(): PDO
    {
        return $this->pdo;
    }

    /**
     * Check if a column in a table has a UNIQUE constraint
     * @param string $columnName The name of the column
     * @return bool True if the column has a UNIQUE constraint, otherwise false
     * @throws Exception
     */
    public function isColumnUnique(string $columnName): bool
    {
        if ($this->getDatabaseType() === DatabaseType::postgres) {
            $query = "SELECT COUNT(*) FROM pg_constraint c
                     JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY(c.conkey)
                     WHERE c.conrelid = '" . $this->getName() . "'::regclass
                     AND c.contype = 'u'
                     AND a.attname = '" . $columnName . "'";

            $result = $this->pdo->query($query)->fetchColumn();
            return (int)$result > 0;
        } else {
            $tablesDetails = DatabaseManager::getTableDetails();

            if (!isset($tablesDetails[$this->getName()])) {
                return false;
            }

            foreach ($tablesDetails[$this->getName()] as $columnDetails) {
                if ($columnDetails['COLUMN_NAME'] === $columnName && $columnDetails['COLUMN_KEY'] === 'UNI') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get bind list
     * @return array
     */
    public function getBind(): array
    {
        if (!isset($this->bind)) {
            return [];
        }
        
        return $this->bind;
    }

}
