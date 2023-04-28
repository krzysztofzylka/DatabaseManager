<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseException;
use krzysztofzylka\DatabaseManager\Helper\SqlBuilder;
use krzysztofzylka\DatabaseManager\Trait\TableBind;
use krzysztofzylka\SimpleLibraries\Library\_Array;
use PDO;

class Table
{

    use TableBind;

    /**
     * Table name
     * @var string
     */
    private string $name;

    /**
     * ID database element
     * @var ?int
     */
    private ?int $id;

    /**
     * Initialize
     * @param string $name table name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
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
     * Get id
     * @return ?int
     */
    public function getId(): ?int
    {
        if (!isset($this->id)) {
            return null;
        }

        return $this->id;
    }

    /**
     * Set id
     * @param ?int $id
     * @return Table
     */
    public function setId(?int $id = null): Table
    {
        $this->id = $id;

        return $this;
    }

    /**
     * PDO Instance
     * @return PDO
     */
    public function getPdoInstance(): PDO
    {
        return DatabaseManager::getPdoInstance();
    }

    /**
     * Query
     * @param string $sql
     * @return array
     * @throws DatabaseException
     */
    public function query(string $sql): array
    {
        try {
            return DatabaseManager::query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Exec
     * @param string $statement
     * @return int|false
     * @throws DatabaseException
     */
    public function exec(string $statement): false|int
    {
        try {
            DatabaseManager::setLastSql($statement);

            return DatabaseManager::getPdoInstance()->exec($statement);
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Table exists in database
     * @return bool
     * @throws DatabaseException
     */
    public function exists(): bool
    {
        try {
            $sql = SqlBuilder::showTables($this->getName());
            $query = DatabaseManager::query($sql);

            return !empty($query->fetchAll());
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Delete
     * @param ?int $id
     * @return bool
     * @throws DatabaseException
     */
    public function delete(?int $id = null): bool
    {
        $id ??= $this->getId();

        if (is_null($id)) {
            throw new DatabaseException('ID must be defined');
        }

        try {
            $sql = SqlBuilder::delete(
                $this->getName(),
                Helper\Table::prepareColumnNameWithAlias($this->getName() . '.id') . ' = ' . $id
            );
            DatabaseManager::setLastSql($sql);

            return $this->exec($sql);
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Save data
     * @param array $data
     * @return bool
     * @throws DatabaseException
     */
    public function save(array $data): bool
    {
        if (!is_null($this->getId())) {
            $set = [];

            foreach (array_keys($data) as $name) {
                $set[] = '`' . $name . '` = :' . $name;
            }

            try {
                $sql = 'UPDATE `' . $this->getName() . '` SET ' . implode(', ', $set) . ' WHERE ' . Helper\Table::prepareColumnNameWithAlias($this->getName() . '.id') . '=' . $this->getId();
                DatabaseManager::setLastSql($sql);
                $update = $this->getPdoInstance()->prepare($sql);

                foreach ($data as $name => $value) {
                    $update->bindValue(':' . $name, $value);
                }

                return $update->execute();
            } catch (Exception $exception) {
                throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
            }
        } else {
            try {
                $sql = 'INSERT INTO `' . $this->getName() . '` (`' . implode('`, `', array_keys($data)) . '`) VALUES (:' . implode(', :', array_keys($data)) . ')';
                DatabaseManager::setLastSql($sql);
                $insert = $this->getPdoInstance()->prepare($sql);

                foreach ($data as $name => $value) {
                    $insert->bindValue(':' . $name, $value);
                }

                if ($insert->execute()) {
                    $lastInsertId = (int)$this->getPdoInstance()->lastInsertId();

                    if ($lastInsertId > 0) {
                        $this->setId($lastInsertId);
                    }

                    return true;
                } else {
                    $this->setId(null);

                    return false;
                }
            } catch (Exception $exception) {
                throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }
    }

    /**
     * Update single column
     * @param string $columnName
     * @param mixed $value
     * @return bool
     * @throws DatabaseException
     */
    public function updateValue(string $columnName, mixed $value): bool
    {
        if (is_null($this->getId())) {
            throw new DatabaseException('ID is not defined');
        }

        try {
            $sql = 'UPDATE `' . $this->getName() . '` SET `' . $columnName . '` = :' . $columnName . ' WHERE ' . Helper\Table::prepareColumnNameWithAlias($this->getName() . '.id') . '=' . $this->getId();
            DatabaseManager::setLastSql($sql);
            $update = $this->getPdoInstance()->prepare($sql);
            $update->bindValue(':' . $columnName, $value);

            return $update->execute();
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Find one element
     * @param array|null $condition
     * @param array|null $columns
     * @param ?string $orderBy
     * @return array
     * @throws DatabaseException
     */
    public function find(?array $condition = null, ?array $columns = null, ?string $orderBy = null): array
    {
        $sql = SqlBuilder::select(
            $columns ? $this->prepareCustomColumnList($columns) : $this->prepareColumnListForSql(),
            $this->getName(),
            trim(implode(' ', $this->prepareBindData())),
            $condition ? (new Where())->getPrepareConditions($condition) : null,
            null,
            $orderBy
        );

        DatabaseManager::setLastSql($sql);

        try {
            $pdo = DatabaseManager::prepare($sql);
            $pdo->execute();
            $fetchData = $pdo->fetch(PDO::FETCH_ASSOC);

            return $this->prepareReturnValue($fetchData);
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }


    /**
     * prepare column list
     * @param bool $asString
     * @return array|string
     * @throws DatabaseException
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
     * Get column list
     * @param ?string $columnName column name
     * @return array|bool
     * @throws DatabaseException
     */
    public function columnList(?string $columnName = null): array|bool
    {
        try {
            $return = [];
            $cacheData = DatabaseManager::$cache->get('columnList_' . $this->getName());

            if (!is_null($cacheData)) {
                $return = $cacheData;
            } else {
                if (DatabaseManager::getDatabaseType() === DatabaseType::sqlite) {
                    $sql = 'pragma table_info("user");';
                    $data = DatabaseManager::query($sql)->fetchAll(PDO::FETCH_ASSOC);

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
                    $return = DatabaseManager::query($sql)->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            if (!is_null($columnName)) {
                foreach ($return as $data) {
                    if ($data['Field'] === $columnName) {
                        return $data;
                    }
                }
            }

            DatabaseManager::$cache->set('columnList_' . $this->getName(), $return);

            return $return ?? false;
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Prepare return data
     * @param false|array $data
     * @return array
     */
    private function prepareReturnValue(false|array $data): array
    {
        if (!$data) {
            return [];
        }

        $returnData = [];
        $hasOneBinds = [];
        $issetData = [];

        if (isset($this->bind)) {
            foreach ($this->bind as $bind) {
                if ($bind['type'] === '#HAS_ONE#') {
                    $hasOneBinds[] = str_replace('`', '', $bind['primaryKey']);
                }
            }
        }

        if (isset(array_keys($data)[0]) && is_int(array_keys($data)[0])) {
            foreach ($data as $key => $insideData) {
                if (!empty($hasOneBinds)) {
                    foreach ($hasOneBinds as $hasOneBind) {
                        if (isset($issetData[$hasOneBind]) && in_array($insideData[$hasOneBind], $issetData[$hasOneBind])) {
                            continue 2;
                        }

                        $issetData[$hasOneBind][] = $insideData[$hasOneBind];
                    }
                }

                foreach ($insideData as $name => $value) {
                    $explode = explode('.', $name, 2);
                    $returnData[$key][$explode[0]][$explode[1]] = $value;
                }
            }

            if (isset($this->bind)) {
                $returnDataNew = [];
                $hasManyIds = [];

                foreach ($this->bind as $bind) {
                    if ($bind['type'] === '#HAS_MANY#') {
                        for ($i = 0; $i < count($returnData); $i++) {
                            $dataId = _Array::getFromArrayUsingString(str_replace('`', '', $bind['primaryKey']), $returnData[$i]);

                            if (_Array::inArrayKeys($dataId, $hasManyIds)) {
                                $returnDataNew[$hasManyIds[$dataId]][$bind['tableName']][] = $returnData[$i][$bind['tableName']];
                            } else {
                                $addData = $returnData[$i];
                                $addData[$bind['tableName']] = [$returnData[$i][$bind['tableName']]];
                                $returnDataNew[] = $addData;
                                $hasManyIds[$dataId] = array_key_last($returnDataNew);
                            }
                        }

                        $returnData = $returnDataNew;
                    }
                }
            }

            return array_values($returnData);
        } else {
            foreach ($data as $name => $value) {
                $explode = explode('.', $name, 2);
                $returnData[$explode[0]][$explode[1]] = $value;
            }

            return $returnData;
        }
    }

}