<?php

namespace krzysztofzylka\DatabaseManager;

use Exception;
use krzysztofzylka\DatabaseManager\Exception\DatabaseException;
use krzysztofzylka\DatabaseManager\Helper\SqlBuilder;
use krzysztofzylka\DatabaseManager\Trait\TableBind;
use PDO;

class Table
{

    use TableBind;

    /**
     * Table PDO Instance
     * @var PDO
     */
    private PDO $pdoInstance;

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
        $this->pdoInstance = DatabaseManager::getPdoInstance();
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
        return $this->id;
    }

    /**
     * Set id
     * @param ?int $id
     * @return Table
     */
    public function setId(?int $id): Table
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
        return $this->pdoInstance;
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
                    $this->setId($this->getPdoInstance()->lastInsertId());

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

}