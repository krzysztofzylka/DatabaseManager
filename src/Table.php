<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use InvalidArgumentException;
use krzysztofzylka\DatabaseManager\Dialect\DatabaseDialect;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Helper\SqlIdentifier;
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
            } catch (ConnectException $e) {
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
     * Set the column name
     * @param string $name
     * @return Table
     */
    public function setName(string $name): self
    {
        $this->name = SqlIdentifier::validate($name);

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
     * @throws DatabaseManagerException
     */
    public function setConnection(string $connectionName): self
    {
        try {
            $connection = ConnectionManager::getConnection($connectionName);
            $this->pdo = $connection->getConnection();
            $this->connectionName = $connectionName;
            $this->databaseType = $connection->getType();
        } catch (ConnectException $e) {
            throw new DatabaseManagerException("Connection '$connectionName' not found");
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
     * Get SQL dialect of the current connection
     * @return DatabaseDialect
     */
    private function getDialect(): DatabaseDialect
    {
        return $this->getDatabaseType()->getDialect();
    }

    /**
     * Get SQL identifier quote character
     * @return string
     */
    private function getIdQuote(): string
    {
        return $this->getDialect()->getIdentifierQuote();
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

        if (!is_null($tableName)) {
            SqlIdentifier::validate($tableName);
        }

        if (!is_null($primaryKey)) {
            SqlIdentifier::validateQualified($primaryKey);
        }

        if (!is_null($foreignKey)) {
            SqlIdentifier::validateQualified($foreignKey);
        }

        if (!is_null($tableAlias)) {
            SqlIdentifier::validate($tableAlias);
        }

        $quote = $this->getIdQuote();
        $primaryKey = $primaryKey ? Helper\Table::prepareColumnNameWithAlias($primaryKey, $quote) : ($quote . $this->getName() . $quote . '.' . $quote . 'id' . $quote);
        $foreignKey = $foreignKey ? Helper\Table::prepareColumnNameWithAlias($foreignKey, $quote) : ($quote . $tableName . $quote . '.' . $quote . $this->getName() . '_id' . $quote);

        if (isset($this->bind) && is_array($this->bind)) {
            foreach ($this->bind as $idx => $b) {
                if (!empty($tableAlias) && isset($b['tableAlias']) && $b['tableAlias'] === $tableAlias) {
                    unset($this->bind[$idx]);
                } elseif (empty($tableAlias) && empty($b['tableAlias']) && isset($b['tableName']) && $b['tableName'] === $tableName) {
                    unset($this->bind[$idx]);
                }
            }
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
     * Get a column list
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
                $return = $this->getDialect()->columnList($this->pdo, $this->getName());
            }

            if (!is_null($columnName)) {
                foreach ($return as $data) {
                    if ($data['Field'] === $columnName) {
                        return $data;
                    }
                }

                return false;
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

            $sql .= $this->getDialect()->insertReturningClause();

            DatabaseManager::setLastSql($sql);
            $insert = $this->pdo->prepare($sql);

            foreach ($data as $name => $value) {
                $insert->bindValue(':' . $name, $value);
            }

            if ($insert->execute()) {
                $this->setId($this->getDialect()->lastInsertId($insert, $this->pdo));

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
     * Delete by conditions
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

            $whereData = $whereHelper->getPrepareConditions($condition);
            $bindValues = $whereData['bind'];
            $whereData = $whereData['sql'];

            $sql = 'DELETE FROM ' . $quote . $this->getName() . $quote . ' WHERE ' . $whereData;
            DatabaseManager::setLastSql($sql);

            $stmt = $this->pdo->prepare($sql);

            foreach ($bindValues as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            return $stmt->execute();
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
        return $this->getDialect()->tableExists($this->pdo, $this->getName());
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
     * @throws InvalidArgumentException when the column name is not a valid identifier
     */
    public function isColumnUnique(string $columnName): bool
    {
        SqlIdentifier::validate($columnName);

        return $this->getDialect()->isColumnUnique($this->pdo, $this->getName(), $columnName);
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
