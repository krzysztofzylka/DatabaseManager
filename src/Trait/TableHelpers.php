<?php

namespace krzysztofzylka\DatabaseManager\Trait;

use InvalidArgumentException;
use Krzysztofzylka\Arrays\Arrays;
use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\GroupBy;
use krzysztofzylka\DatabaseManager\Helper\Where;
use krzysztofzylka\DatabaseManager\OrderBy;

trait TableHelpers
{

    /**
     * Normalize a raw string, a single OrderBy/GroupBy, or an array of them into the
     * plain comma-separated string the existing SQL-building pipeline expects
     * @param string|OrderBy|GroupBy|array|null $value
     * @return ?string
     */
    private function normalizeSqlList(string|OrderBy|GroupBy|array|null $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }

        return (string)$value;
    }

    /**
     * Normalize the limit/offset pair into the raw string the existing SQL-building
     * pipeline expects, validating the structured (integer) form
     * @param string|int|null $limit
     * @param ?int $offset
     * @return ?string
     * @throws InvalidArgumentException
     */
    private function normalizeLimit(string|int|null $limit, ?int $offset): ?string
    {
        if (is_null($limit)) {
            if (!is_null($offset)) {
                throw new InvalidArgumentException('offset can only be used together with a limit');
            }

            return null;
        }

        if (is_string($limit)) {
            if (!is_null($offset)) {
                throw new InvalidArgumentException('offset can only be combined with an integer limit, not a raw limit string');
            }

            return $limit;
        }

        if ($limit < 0) {
            throw new InvalidArgumentException('limit must not be negative');
        }

        if (is_null($offset)) {
            return (string)$limit;
        }

        if ($offset < 0) {
            throw new InvalidArgumentException('offset must not be negative');
        }

        return $limit . ' OFFSET ' . $offset;
    }

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
                    $hasOneBinds[] = str_replace(['`', '"'], '', $bind['primaryKey']);
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
                            $dataId = Arrays::getFromArrayUsingString(str_replace(['`', '"'], '', $bind['primaryKey']), $returnData[$i]);

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
     * @param int $bindIndex
     * @return array
     * @throws DatabaseManagerException
     */
    private function prepareBindData(int &$bindIndex = 0): array
    {
        if (!isset($this->bind)) {
            return [
                'sql' => [],
                'bind' => []
            ];
        }

        $return = [];
        $bindValues = [];

        $quote = $this->getIdQuote();

        foreach ($this->bind as $bind) {
            if ($bind['type'] === '#HAS_ONE#') {
                $bind['type'] = BindType::leftJoin->value;
            } elseif ($bind['type'] === '#HAS_MANY#') {
                $bind['type'] = BindType::leftJoin->value;
            }

            $foreignKey = $bind['foreignKey'];
            $joinTable = $quote . $bind['tableName'] . $quote;

            if (!empty($bind['tableAlias'])) {
                $joinTable .= ' as ' . $quote . $bind['tableAlias'] . $quote;
                $foreignKey = $this->replaceTableWithAlias($foreignKey, $bind['tableName'], $bind['tableAlias']);
            }

            $bindData = $bind['type'] . ' ' . $joinTable . ' ON ' . $bind['primaryKey'] . ' = ' . $foreignKey;

            if (!is_null($bind['condition'])) {
                $isNotEmpty = false;
                $conditionSql = null;
                $conditionBind = [];

                if ($bind['condition'] instanceof Condition) {
                    $bind['condition']->setDatabaseType($this->getDatabaseType());
                    $compiled = $bind['condition']->toSql($bindIndex);
                    $conditionSql = trim($compiled['sql']);
                    $conditionBind = $compiled['bind'];
                    $isNotEmpty = $conditionSql !== '';
                } elseif (is_array($bind['condition'])) {
                    $isNotEmpty = count($bind['condition']) > 0;
                }

                if ($isNotEmpty) {
                    if ($bind['condition'] instanceof Condition) {
                        if (!empty($bind['tableAlias'])) {
                            $conditionSql = $this->replaceTableWithAlias($conditionSql, $bind['tableName'], $bind['tableAlias']);
                        }
                        $bindData .= ' AND ' . $conditionSql;
                        $bindValues = array_merge($bindValues, $conditionBind);
                    } else {
                        $whereHelper = new Where();
                        $whereHelper->setDatabaseType($this->getDatabaseType());

                        if (!empty($bind['tableAlias'])) {
                            $modifiedCondition = [];

                            foreach ($bind['condition'] as $key => $value) {
                                if (!str_contains($key, '.') && !in_array(strtoupper($key), ['AND', 'OR'])) {
                                    $modifiedCondition[$bind['tableAlias'] . '.' . $key] = $value;
                                } else {
                                    $modifiedCondition[$key] = $value;
                                }
                            }
                            $whereData = $whereHelper->getPrepareConditions($modifiedCondition, 'AND', $bindIndex);
                        } else {
                            $whereData = $whereHelper->getPrepareConditions($bind['condition'], 'AND', $bindIndex);
                        }

                        $bindData .= ' AND ' . $whereData['sql'];
                        $bindValues = array_merge($bindValues, $whereData['bind']);
                    }
                }
            }

            $return[] = $bindData;
        }

        return [
            'sql' => $return,
            'bind' => $bindValues
        ];
    }

    /**
     * Point column references at the join alias instead of the joined table name
     * @param string $sql
     * @param string $tableName
     * @param string $alias
     * @return string
     */
    private function replaceTableWithAlias(string $sql, string $tableName, string $alias): string
    {
        $quote = $this->getIdQuote();

        return str_replace(
            [$quote . $tableName . $quote . '.', $tableName . '.'],
            [$quote . $alias . $quote . '.', $alias . '.'],
            $sql
        );
    }

}
