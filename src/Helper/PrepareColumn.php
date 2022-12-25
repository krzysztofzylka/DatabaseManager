<?php

namespace DatabaseManager\Helper;

use DatabaseManager\Column;
use DatabaseManager\DatabaseManager;
use DatabaseManager\Enum\ColumnDefault;
use DatabaseManager\Enum\DatabaseType;

class PrepareColumn {

    /**
     * Generate column sql
     * @param Column $column
     * @return string
     */
    public static function generateCreateColumnSql(Column $column) : string {
        return trim(
            '`' . $column->getName() . '` '
            . strtoupper($column->getType()->name)
            . ($column->getTypeSize() ? '(' . $column->getTypeSize() . ') ' : ' ')
            . ($column->isNull() ? 'NULL ' : 'NOT NULL ')
            . self::prepareDefault($column)
            . (($column->isAutoincrement() && DatabaseManager::getDatabaseType() === DatabaseType::mysql) ? 'AUTO_INCREMENT ' : ' ')
            . (DatabaseManager::getDatabaseType() === DatabaseType::sqlite && $column->isPrimary() ? 'PRIMARY KEY ' : ' ')
            . ($column->getExtra())
        );
    }

    /**
     * Prepare DEFAULT string for column
     * @param Column $column
     * @return ?string
     */
    private static function prepareDefault(Column $column) : ?string {
        if (!$column->isDefaultDefined()) {
            return '';
        }

        if ($column->getDefault() instanceof ColumnDefault) {
            return 'DEFAULT ' . $column->getDefault()->value . ' ';
        } elseif (is_string($column->getDefault())) {
            if (str_contains($column->getDefault(), "'")) {
                return 'DEFAULT "' . $column->getDefault() . '" ';
            } else {
                return 'DEFAULT \'' . $column->getDefault() . '\' ';
            }
        } else {
            return 'DEFAULT ' . $column->getDefault() . ' ';
        }
    }

}