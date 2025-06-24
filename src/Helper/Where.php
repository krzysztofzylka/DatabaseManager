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
        $this->databaseType = DatabaseType::mysql;  // Default to MySQL
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
     * Prepare conditions
     * @param array $data
     * @param string $type
     * @return string
     * @throws DatabaseManagerException
     */
    public function getPrepareConditions(array $data, string $type = 'AND'): string
    {
        try {
            $sqlArray = [];
            $quote = $this->getQuote();

            foreach ($data as $nextType => $conditionValue) {
                if ($conditionValue instanceof Condition) {
                    $conditionValue->setDatabaseType($this->databaseType);
                    $sqlArray[] = (string)$conditionValue;
                } elseif (is_array($conditionValue)) {
                    $sqlArray[] = $this->getPrepareConditions($conditionValue, is_int($nextType) ? 'AND' : $nextType);
                } else {
                    $sqlArray[] = Table::prepareColumnNameWithAlias($nextType, $quote) . ' = ' . $this->prepareValue($conditionValue);
                }
            }

            return '(' . implode(" $type ", $sqlArray) . ')';
        } catch (Exception $exception) {
            throw new DatabaseManagerException($exception->getMessage());
        }
    }

}