<?php

namespace krzysztofzylka\DatabaseManager\Dialect;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use PDO;
use PDOException;

class SqliteDialect extends AbstractDialect
{

    public function createPdo(DatabaseConnect $connect): PDO
    {
        try {
            return new PDO('sqlite:' . $connect->getSqlitePath());
        } catch (PDOException $exception) {
            throw new ConnectException($exception->getMessage());
        }
    }

    public function getIdentifierQuote(): string
    {
        return '`';
    }

    public function columnDefinition(Column $column): string
    {
        // SQLite only allows AUTOINCREMENT on a column declared exactly as INTEGER PRIMARY KEY
        if ($column->isAutoincrement()) {
            return $this->joinParts([
                $this->quoteIdentifier($column->getName()),
                'INTEGER PRIMARY KEY AUTOINCREMENT',
                $column->getExtra() ?? '',
            ]);
        }

        return $this->joinParts([
            $this->quoteIdentifier($column->getName()),
            strtoupper($column->getType()->name) . ($column->getTypeSize() ? '(' . $column->getTypeSize() . ')' : ''),
            $column->isUnsigned() ? 'UNSIGNED' : '',
            !is_null($column->isNull()) ? ($column->isNull() ? 'NULL' : 'NOT NULL') : '',
            $this->defaultClause($column),
            $column->isPrimary() ? 'PRIMARY KEY' : '',
            $column->getExtra() ?? '',
        ]);
    }

    public function columnList(PDO $pdo, string $tableName): array
    {
        $columns = [];

        foreach ($this->run($pdo, 'pragma table_info("' . $tableName . '");')->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[] = [
                'Field' => $column['name'],
                'Type' => $column['type'],
                'Null' => $column['notnull'] ? 'NO' : 'YES',
                'Key' => $column['pk'] ? 'PRI' : '',
                'Default' => $column['dflt_value'],
                'Extra' => ''
            ];
        }

        return $columns;
    }

    public function tableExists(PDO $pdo, string $tableName): bool
    {
        // The name is a validated identifier, so it is safe to embed
        $sql = "SELECT sql FROM sqlite_master WHERE type='table' AND name = '" . $tableName . "';";

        return !empty($this->run($pdo, $sql)->fetchAll());
    }

    public function listTables(PDO $pdo): array
    {
        return array_column($this->fetchRows($this->run($pdo, "SELECT name FROM sqlite_master WHERE type='table'"), PDO::FETCH_NUM), 0);
    }

    public function tableDetails(PDO $pdo): array
    {
        $details = [];

        foreach ($this->listTables($pdo) as $tableName) {
            $details[$tableName] = [];

            foreach ($this->fetchRows($this->run($pdo, "PRAGMA table_info('" . $tableName . "')")) as $column) {
                $details[$tableName][] = [
                    'column_name' => $column['name'],
                    'column_type' => $column['type'],
                    'is_nullable' => $column['notnull'] === 1 ? 'NO' : 'YES',
                    'default_value' => $column['dflt_value'],
                    'is_primary_key' => $column['pk'] === 1 ? 'YES' : 'NO',
                ];
            }
        }

        return $details;
    }

    public function isColumnUnique(PDO $pdo, string $tableName, string $columnName): bool
    {
        foreach ($this->fetchRows($this->run($pdo, 'PRAGMA index_list("' . $tableName . '")')) as $index) {
            if (!$index['unique'] || $index['origin'] === 'pk') {
                continue;
            }

            $indexColumns = $this->fetchRows($this->run($pdo, 'PRAGMA index_info("' . str_replace('"', '""', $index['name']) . '")'));

            if (count($indexColumns) === 1 && $indexColumns[0]['name'] === $columnName) {
                return true;
            }
        }

        return false;
    }

}
