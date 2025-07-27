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
                } elseif (is_array($conditionValue) && !empty($conditionValue) && array_keys($conditionValue) !== range(0, count($conditionValue) - 1)) {
                    $result = $this->getPrepareConditions($conditionValue, is_int($nextType) ? 'AND' : $nextType, $bindIndex);
                    $sqlArray[] = $result['sql'];
                    $bindValues = array_merge($bindValues, $result['bind']);
                } else {
                    $bindKey = ':bind_' . $bindIndex++;
                    $sqlArray[] = Table::prepareColumnNameWithAlias($nextType, $quote) . ' = ' . $bindKey;
                    $bindValues[$bindKey] = $conditionValue;
                }
            }

            return [
                'sql' => '(' . implode(" $type ", $sqlArray) . ')',
                'bind' => $bindValues
            ];
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
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