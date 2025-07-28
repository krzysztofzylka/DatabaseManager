<?php

namespace krzysztofzylka\DatabaseManager\Helper;

use Exception;
use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Trait\ConditionMethods;

class Where
{

    use ConditionMethods;

    /**
     * Database type for SQL generation
     * @var DatabaseType
     */
    private DatabaseType $databaseType;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->databaseType = DatabaseType::mysql;
    }

    /**
     * Set database type
     * @param DatabaseType $databaseType
     * @return $this
     */
    public function setDatabaseType(DatabaseType $databaseType): self
    {
        $this->databaseType = $databaseType;
        return $this;
    }

    /**
     * Get quote character for identifiers
     * @return string
     */
    private function getQuote(): string
    {
        return $this->databaseType === DatabaseType::postgres ? '"' : '`';
    }

    /**
     * Prepare conditions with bind parameters
     * @param array $data
     * @param string $type
     * @param int $bindIndex
     * @return array ['sql' => string, 'bind' => array]
     * @throws DatabaseManagerException
     */
    public function getPrepareConditions(array $data, string $type = 'AND', int &$bindIndex = 0): array
    {
        try {
            $sqlArray = [];
            $bindValues = [];
            $quote = $this->getQuote();

            foreach ($data as $nextType => $conditionValue) {
                if ($conditionValue instanceof Condition) {
                    $conditionValue->setDatabaseType($this->databaseType);
                    $sqlArray[] = (string)$conditionValue;
                } elseif (is_array($conditionValue) && !empty($conditionValue)) {
                    $isIndexedArray = array_keys($conditionValue) === range(0, count($conditionValue) - 1);

                    if ($isIndexedArray) {
                        $subConditions = $this->processIndexedConditions($conditionValue, $bindIndex);

                        if (!empty($subConditions['sql'])) {
                            $sqlArray[] = $subConditions['sql'];
                            $bindValues = array_merge($bindValues, $subConditions['bind']);
                        }
                    } else {
                        $subType = is_string($nextType) ? $nextType : 'AND';
                        $result = $this->getPrepareConditions($conditionValue, $subType, $bindIndex);

                        if (!empty($result['sql'])) {
                            $sqlArray[] = $result['sql'];
                            $bindValues = array_merge($bindValues, $result['bind']);
                        }
                    }
                } elseif (is_array($conditionValue) && count($conditionValue) === 2 && is_string($conditionValue[0])) {
                    $operator = $conditionValue[0];
                    $value = $conditionValue[1];
                    $bindKey = ':bind_' . $bindIndex++;
                    $sqlArray[] = Table::prepareColumnNameWithAlias($nextType, $quote) . ' ' . $operator . ' ' . $bindKey;
                    $bindValues[$bindKey] = $value;
                } else {
                    $bindKey = ':bind_' . $bindIndex++;
                    $columnName = $nextType;

                    if (!str_contains($columnName, '.')) {
                        $columnName = Table::prepareColumnNameWithAlias($columnName, $quote);
                    }

                    $sqlArray[] = $columnName . ' = ' . $bindKey;
                    $bindValues[$bindKey] = $conditionValue;
                }
            }

            $sql = empty($sqlArray) ? '' : '(' . implode(" $type ", $sqlArray) . ')';

            return [
                'sql' => $sql,
                'bind' => $bindValues
            ];
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

    /**
     * @param array $conditions
     * @param int $bindIndex
     * @return array
     * @throws DatabaseManagerException
     */
    private function processIndexedConditions(array $conditions, int &$bindIndex): array
    {
        $sqlParts = [];
        $bindValues = [];

        foreach ($conditions as $condition) {
            if ($condition instanceof Condition) {
                $condition->setDatabaseType($this->databaseType);
                $sqlParts[] = (string)$condition;
            } elseif (is_array($condition)) {
                $result = $this->getPrepareConditions($condition, 'AND', $bindIndex);

                if (!empty($result['sql'])) {
                    $sqlParts[] = $result['sql'];
                    $bindValues = array_merge($bindValues, $result['bind']);
                }
            }
        }

        $sql = empty($sqlParts) ? '' : '(' . implode(' AND ', $sqlParts) . ')';

        return [
            'sql' => $sql,
            'bind' => $bindValues
        ];
    }


    /**
     * Prepare conditions (legacy method for backward compatibility)
     * @param array $data
     * @param string $type
     * @return string
     * @throws DatabaseManagerException
     * @deprecated Use getPrepareConditions() instead
     */
    public function getPrepareConditionsLegacy(array $data, string $type = 'AND'): string
    {
        $result = $this->getPrepareConditions($data, $type);
        return $result['sql'];
    }

}