<?php

namespace krzysztofzylka\DatabaseManager\Helper;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\ColumnDefault;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;

class PrepareColumn
{

    /**
     * Generate column sql
     * @param Column $column
     * @param DatabaseType|null $databaseType
     * @return string
     * @throws ConnectException
     */
    public static function generateCreateColumnSql(Column $column, ?DatabaseType $databaseType = null): string
    {
        $databaseType = $databaseType ?? DatabaseManager::getDatabaseType();

        if ($databaseType === DatabaseType::postgres) {
            return self::generatePostgresColumnSql($column);
        } else {
            return self::generateMySQLColumnSql($column);
        }
    }

    /**
     * Generate column SQL for MySQL
     * @param Column $column
     * @return string
     * @throws ConnectException
     */
    private static function generateMySQLColumnSql(Column $column): string
    {
        return trim(
            '`' . $column->getName() . '` '
            . strtoupper($column->getType()->name)
            . ($column->getTypeSize() ? '(' . $column->getTypeSize() . ') ' : ' ')
            . ($column->isUnsigned() ? 'UNSIGNED ' : '')
            . (!is_null($column->isNull()) ? ($column->isNull() ? 'NULL ' : 'NOT NULL ') : '')
            . self::prepareDefault($column, DatabaseType::mysql)
            . (($column->isAutoincrement() && DatabaseManager::getDatabaseType() === DatabaseType::mysql) ? 'AUTO_INCREMENT ' : ' ')
            . (DatabaseManager::getDatabaseType() === DatabaseType::sqlite && $column->isPrimary() ? 'PRIMARY KEY ' : ' ')
            . (($column->isAutoincrement() && DatabaseManager::getDatabaseType() === DatabaseType::sqlite) ? 'AUTOINCREMENT ' : ' ')
            . ($column->getExtra())
        );
    }

    /**
     * Generate column SQL for PostgreSQL
     * @param Column $column
     * @return string
     */
    private static function generatePostgresColumnSql(Column $column): string
    {
        $typeName = self::mapColumnTypeToPostgres($column->getType()->name);
        $sizeString = '';

        if ($column->getTypeSize() && !$column->isAutoincrement()) {
            // Sprawdź typy, które nie powinny mieć rozmiaru w PostgreSQL
            $typesWithoutSize = [
                'smallint', 'integer', 'bigint', 'boolean', 'text', 'bytea',
                'date', 'time', 'timestamp', 'real', 'double precision'
            ];

            if (!in_array(strtolower($typeName), $typesWithoutSize)) {
                if ($column->getType() === ColumnType::enum) {
                    $sizeString = '(255)';
                } elseif (strtolower($typeName) === 'numeric' && strpos($column->getTypeSize(), ',') !== false) {
                    $sizeString = '(' . $column->getTypeSize() . ')';
                } elseif (strtolower($typeName) === 'character varying' || strtolower($typeName) === 'character') {
                    if ($column->getType() === ColumnType::enum) {
                        $sizeString = '(255)';
                    } else {
                        $sizeString = '(' . $column->getTypeSize() . ')';
                    }
                }
            }
        }

        if ($column->isAutoincrement()) {
            if ($column->getType()->name === 'int' || $column->getType()->name === 'integer') {
                $typeName = 'SERIAL';
            } elseif ($column->getType()->name === 'bigint') {
                $typeName = 'BIGSERIAL';
            } elseif ($column->getType()->name === 'smallint') {
                $typeName = 'SMALLSERIAL';
            }
        }

        return trim(
            '"' . $column->getName() . '" '
            . strtoupper($typeName)
            . $sizeString . ' '
            . (!is_null($column->isNull()) ? ($column->isNull() ? 'NULL ' : 'NOT NULL ') : '')
            . self::prepareDefault($column, DatabaseType::postgres)
            . ($column->isPrimary() && !$column->isAutoincrement() ? 'PRIMARY KEY ' : '')
            . ($column->getExtra() ?? '')
        );
    }

    /**
     * Map MySQL column type to PostgreSQL type
     * @param string $mysqlType
     * @return string
     */
    private static function mapColumnTypeToPostgres(string $mysqlType): string
    {
        $mapping = [
            'int' => 'integer',
            'tinyint' => 'smallint',
            'smallint' => 'smallint',
            'mediumint' => 'integer',
            'bigint' => 'bigint',
            'float' => 'real',
            'double' => 'double precision',
            'decimal' => 'numeric',
            'dec' => 'numeric',
            'varchar' => 'character varying',
            'char' => 'character',
            'tinytext' => 'text',
            'mediumtext' => 'text',
            'longtext' => 'text',
            'tinyblob' => 'bytea',
            'blob' => 'bytea',
            'mediumblob' => 'bytea',
            'longblob' => 'bytea',
            'datetime' => 'timestamp',
            'timestamp' => 'timestamp',
            'time' => 'time',
            'date' => 'date',
            'enum' => 'character varying',
            'set' => 'character varying',
            'bit' => 'bit',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'json' => 'jsonb',
            'year' => 'integer',
        ];

        return $mapping[$mysqlType] ?? $mysqlType;
    }

    /**
     * Prepare DEFAULT string for column
     * @param Column $column
     * @param DatabaseType $databaseType
     * @return ?string
     */
    private static function prepareDefault(Column $column, DatabaseType $databaseType): ?string
    {
        if (!$column->isDefaultDefined()) {
            return '';
        }

        if (is_null($column->getDefault())) {
            return 'DEFAULT NULL';
        } elseif ($column->getDefault() instanceof ColumnDefault) {
            if ($databaseType === DatabaseType::postgres) {
                if ($column->getDefault() === ColumnDefault::currentTimestamp) {
                    return 'DEFAULT CURRENT_TIMESTAMP ';
                }
                return 'DEFAULT ' . $column->getDefault()->value . ' ';
            } else {
                return 'DEFAULT ' . $column->getDefault()->value . ' ';
            }
        } elseif (is_string($column->getDefault())) {
            if ($databaseType === DatabaseType::postgres) {
                return "DEFAULT '" . str_replace("'", "''", $column->getDefault()) . "' ";
            } else {
                if (str_contains($column->getDefault(), "'")) {
                    return 'DEFAULT "' . $column->getDefault() . '" ';
                } else {
                    return 'DEFAULT \'' . $column->getDefault() . '\' ';
                }
            }
        } else {
            return 'DEFAULT ' . $column->getDefault() . ' ';
        }
    }

}