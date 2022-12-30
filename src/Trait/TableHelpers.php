<?php

namespace krzysztofzylka\DatabaseManager\Trait;

trait TableHelpers {

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

    /**
     * Prepare bind data
     * @return array
     */
    private function prepareBindData() : array {
        if (!isset($this->bind)) {
            return [];
        }

        $return = [];

        foreach ($this->bind as $bind) {
            $return[] = $bind['type'] . ' `' . $bind['tableName'] . '` ON ' . $bind['primaryKey'] . ' = ' . $bind['foreignKey'];
        }

        return $return;
    }

}