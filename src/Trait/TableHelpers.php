<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use Krzysztofzylka\Arrays\Arrays;
use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Helper\Where;

trait TableHelpers
{

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
                            $dataId = Arrays::getFromArrayUsingString(str_replace('`', '', $bind['primaryKey']), $returnData[$i]);

                            if (Arrays::inArrayKeys($dataId, $hasManyIds)) {
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
     */
    private function prepareBindData(): array
    {
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

            if (!empty($bind['tableAlias'])) {
                $bind['tableName'] .= '` as `' . $bind['tableAlias'];
            }

            $bindData = $bind['type'] . ' `' . $bind['tableName'] . '` ON ' . $bind['primaryKey'] . ' = ' . $bind['foreignKey'];

            if (!is_null($bind['condition'])) {
                $whereHelper = new Where();
                $whereHelper->setDatabaseType($this->getDatabaseType());

                if ($bind['condition'] instanceof Condition) {
                    $bind['condition']->setDatabaseType($this->getDatabaseType());
                    $bindData .= ' AND ' . (string)$bind['condition'];
                } else {
                    $bindData .= ' AND ' . $whereHelper->getPrepareConditions($bind['condition']);
                }
            }

            $return[] = $bindData;
        }

        return $return;
    }

}