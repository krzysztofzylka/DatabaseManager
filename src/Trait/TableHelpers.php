<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Exception\ConditionException;
use krzysztofzylka\DatabaseManager\Helper\SimpleCondition;

trait TableHelpers {

    /**
     * Prepare return data
     * @param false|array $data
     * @return array
     */
    private function prepareReturnValue(false|array $data) : array {
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

            return array_values($returnData);
        } else {
            foreach ($data as $name => $value) {
                $explode = explode('.', $name, 2);
                $returnData[$explode[0]][$explode[1]] = $value;
            }

            return $returnData;
        }
    }

    /**
     * Prepare bind data
     * @return array
     * @throws ConditionException
     */
    private function prepareBindData() : array {
        if (!isset($this->bind)) {
            return [];
        }

        $return = [];

        foreach ($this->bind as $bind) {
            if ($bind['type'] === '#HAS_ONE#') {
                $bind['type'] = BindType::leftJoin->value;
            }

            $bindData = $bind['type'] . ' `' . $bind['tableName'] . '` ON ' . $bind['primaryKey'] . ' = ' . $bind['foreignKey'];

            if (!is_null($bind['condition'])) {
                if ($bind['condition'] instanceof Condition) {
                    $condition = $bind['condition']->getPrepareConditions();
                } elseif (is_array($bind['condition'])) {
                    $condition = (new SimpleCondition())->getPrepareConditions($bind['condition']);
                }

                $bindData .= ' WHERE ' . $condition;
            }

            $return[] = $bindData;
        }

        return $return;
    }

}