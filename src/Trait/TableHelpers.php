<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Exception\ConditionException;
use krzysztofzylka\DatabaseManager\Helper\Where;
use krzysztofzylka\SimpleLibraries\Library\_Array;

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
            } elseif ($bind['type'] === '#HAS_MANY#') {
                $bind['type'] = BindType::leftJoin->value;
            }

            $bindData = $bind['type'] . ' `' . $bind['tableName'] . '` ON ' . $bind['primaryKey'] . ' = ' . $bind['foreignKey'];

            if (!is_null($bind['condition'])) {
                $bindData .= ' WHERE ' . (new Where())->getPrepareConditions($bind['condition']);
            }

            $return[] = $bindData;
        }

        return $return;
    }

}