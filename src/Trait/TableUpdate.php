<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use Exception;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;

trait TableUpdate
{

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
        $quote = $this->getIdQuote();

        foreach (array_keys($data) as $name) {
            $set[] = $quote . $name . $quote . ' = :' . $name;
        }

        try {
            $sql = 'UPDATE ' . $quote . $this->getName() . $quote . ' SET ' . implode(', ', $set) . ' WHERE id=' . $this->getId();
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
            $quote = $this->getIdQuote();
            $sql = 'UPDATE ' . $quote . $this->getName() . $quote . ' SET ' . $quote . $columnName . $quote . ' = :' . $columnName . ' WHERE id=' . $this->getId();
            DatabaseManager::setLastSql($sql);
            $update = $this->pdo->prepare($sql);
            $update->bindValue(':' . $columnName, $value);

            return $update->execute();
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

}