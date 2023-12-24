<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use krzysztofzylka\DatabaseManager\DatabaseManager;
use Exception;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;

trait TableUpdate
{

    /**
     * Update
     * @param array $data
     * @return bool
     * @throws DatabaseManagerException
     */
    public function update(array $data): bool
    {
        if (is_null($this->getName())) {
            throw new DatabaseManagerException('Table is not defined');
        } elseif (is_null($this->getId())) {
            throw new DatabaseManagerException('ID is not defined');
        }

        $set = [];

        foreach (array_keys($data) as $name) {
            $set[] = '`' . $name . '` = :' . $name;
        }

        try {
            $sql = 'UPDATE `' . $this->getName() . '` SET ' . implode(', ', $set) . ' WHERE id=' . $this->getId();
            DatabaseManager::setLastSql($sql);
            $update = $this->pdo->prepare($sql);

            foreach ($data as $name => $value) {
                $update->bindValue(':' . $name, $value);
            }

            return $update->execute();
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

    /**
     * Update single column
     * @param string $columnName
     * @param mixed $value
     * @return bool
     * @throws DatabaseManagerException
     */
    public function updateValue(string $columnName, mixed $value): bool
    {
        if (is_null($this->getName())) {
            throw new DatabaseManagerException('Table is not defined');
        } elseif (is_null($this->getId())) {
            throw new DatabaseManagerException('ID is not defined');
        }

        try {
            $sql = 'UPDATE `' . $this->getName() . '` SET `' . $columnName . '` = :' . $columnName . ' WHERE id=' . $this->getId();
            DatabaseManager::setLastSql($sql);
            $update = $this->pdo->prepare($sql);
            $update->bindValue(':' . $columnName, $value);

            return $update->execute();
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

}