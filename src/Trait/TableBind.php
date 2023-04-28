<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Helper\Table;

trait TableBind
{

    /**
     * Bind list
     * @var array
     */
    private array $bind;


    /**
     * Bind table
     * @param array|BindType $bind
     * @param string|null $tableName
     * @param ?string $primaryKey
     * @param ?string $foreignKey
     * @param array|Condition|null $condition
     * @return $this
     */
    public function bind(BindType|array $bind, string $tableName = null, ?string $primaryKey = null, ?string $foreignKey = null, null|array|Condition $condition = null) : self {
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

        $primaryKey = $primaryKey ? Table::prepareColumnNameWithAlias($primaryKey) : ('`' . $this->getName() . '`.`id`');
        $foreignKey = $foreignKey ? Table::prepareColumnNameWithAlias($foreignKey) : ('`' . $tableName . '`.`' . $this->getName() . '_id`');

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
    public function unbind(string $tableName) : self {
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
    public function unbindAll() : self {
        $this->bind = [];

        return $this;
    }


}