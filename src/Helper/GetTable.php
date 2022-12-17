<?php

namespace DatabaseManager\Helper;

use DatabaseManager\DatabaseManager;
use DatabaseManager\Enum\DatabaseType;
use PDO;

class GetTable {

    private string $name;
    private PDO $pdo;

    public function __construct() {
        $this->pdo = DatabaseManager::$connection->getConnection();
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
     * Get table name
     * @return string
     */
    private function getName() : string {
        return $this->name;
    }

    /**
     * Find all elements
     * @return array
     */
    public function findAll() {
        $sql = 'SELECT ' . $this->getColumnList() . ' FROM `' . $this->getName() . '`';
        $pdo = $this->pdo->prepare($sql);
        $pdo->execute();
        $fetchData = $pdo->fetchAll(PDO::FETCH_ASSOC);

        return $this->prepareReturnValue($fetchData);
    }

    /**
     * Get column list
     * @return array|bool
     */
    public function columnList() : array|bool {
        if (DatabaseManager::getDatabaseType() === DatabaseType::sqlite) {
            $return = [];
            $data = $this->pdo->query('pragma table_info("user");')->fetchAll(PDO::FETCH_ASSOC);

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

            return $return;
        } elseif (DatabaseManager::getDatabaseType() === DatabaseType::mysql) {
            return $this->pdo->query('DESCRIBE `' . $this->getName() . '`;')->fetchAll(PDO::FETCH_ASSOC);
        }

        return false;
    }

    /**
     * Get column list
     * @param bool $asString
     * @return array|string
     */
    public function getColumnList(bool $asString = true) : array|string {
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
     * Prepare return data
     * @param array $data
     * @return array
     */
    private function prepareReturnValue(array $data) : array {
        $returnData = [];

        if (isset(array_keys($data)[0]) && is_int(array_keys($data)[0])) {
            foreach ($data as $key => $insideData) {
                foreach ($insideData as $name => $value) {
                    $explode = explode('.', $name, 2);
                    $returnData[$key][$explode[0]][$explode[1]] = $value;
                }
            }
        } else {
            foreach ($data as $name => $value) {
                $explode = explode('.', $name, 2);
                $returnData[$explode[0]][$explode[1]] = $value;
            }
        }

        return $returnData;
    }

}