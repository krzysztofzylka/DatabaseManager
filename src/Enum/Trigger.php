<?php

namespace DatabaseManager\Enum;

/**
 * Triggers
 */
enum Trigger {

    case UpdateTimestampAfterUpdate;

    /**
     * Generate SQL
     * @param string $tableName
     * @param string $columnName
     * @return string
     */
    public function generate(string $tableName, string $columnName) : string {
        return match($this) {
            Trigger::UpdateTimestampAfterUpdate => "CREATE TRIGGER `trigger_autoUpdateDateOnUpdate_{$tableName}_{$columnName}` BEFORE UPDATE ON `$tableName` FOR EACH ROW BEGIN SET NEW.`$columnName` = CURRENT_TIMESTAMP; END"
        };
    }

}