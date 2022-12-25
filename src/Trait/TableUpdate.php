<?php

namespace DatabaseManager\Trait;

use DatabaseManager\DatabaseManager;
use DatabaseManager\Exception\UpdateException;

trait TableUpdate {

    /**
     * Update
     * @param array $data
     * @return bool
     * @throws UpdateException
     */
    public function update(array $data) : bool {
        if (is_null($this->getName())) {
            throw new UpdateException('Id is not defined');
        }

        $set = [];

        foreach (array_keys($data) as $name) {
            $set[] = '`' . $name . '` = :' . $name;
        }

        $sql = 'UPDATE `' . $this->getName() . '` SET ' . implode(', ', $set) . ' WHERE id=' . $this->getId();
        DatabaseManager::setLastSql($sql);

        $update = $this->pdo->prepare($sql);

        foreach ($data as $name => $value) {
            $update->bindValue(':' . $name, $value);
        }

        if ($update->execute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update single column
     * @param string $columnName
     * @param mixed $value
     * @return bool
     * @throws UpdateException
     */
    public function updateValue(string $columnName, mixed $value) : bool {
        if (is_null($this->getName())) {
            throw new UpdateException('ID is not defined');
        }

        $sql = 'UPDATE `' . $this->getName() . '` SET `' . $columnName . '` = :' . $columnName . ' WHERE id=' . $this->getId();
        DatabaseManager::setLastSql($sql);
        $update = $this->pdo->prepare($sql);
        $update->bindValue(':' . $columnName, $value);

        if ($update->execute()) {
            return true;
        } else {
            return false;
        }
    }

}