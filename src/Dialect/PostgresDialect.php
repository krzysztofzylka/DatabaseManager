<?php

namespace krzysztofzylka\DatabaseManager\Dialect;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\Enum\ColumnDefault;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use PDO;
use PDOException;
use PDOStatement;

class PostgresDialect extends AbstractDialect
{

    /**
     * MySQL-style column type names mapped to PostgreSQL types
     */
    private const TYPE_MAP = [
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

    /**
     * Serial types used for auto-increment columns
     */
    private const SERIAL_MAP = [
        'int' => 'SERIAL',
        'integer' => 'SERIAL',
        'bigint' => 'BIGSERIAL',
        'smallint' => 'SMALLSERIAL',
    ];

    public function createPdo(DatabaseConnect $connect): PDO
    {
        if (!extension_loaded('pdo_pgsql')) {
            throw new ConnectException('PostgreSQL PDO driver (pdo_pgsql) is not installed.');
        }

        try {
            return new PDO(
                'pgsql:host=' . $connect->getHost()
                . ';dbname=' . $connect->getDatabaseName()
                . ';port=' . $connect->getPort(),
                $connect->getUsername(),
                $connect->getPassword()
            );
        } catch (PDOException $exception) {
            throw new ConnectException($exception->getMessage());
        }
    }

    public function getIdentifierQuote(): string
    {
        return '"';
    }

    public function columnDefinition(Column $column): string
    {
        $typeName = self::TYPE_MAP[$column->getType()->name] ?? $column->getType()->name;
        $size = '';

        if ($column->isAutoincrement() && isset(self::SERIAL_MAP[$column->getType()->name])) {
            $typeName = self::SERIAL_MAP[$column->getType()->name];
        } elseif ($column->getTypeSize() && !$column->isAutoincrement()) {
            if ($column->getType() === ColumnType::enum) {
                $size = '(255)';
            } elseif ($typeName === 'numeric' && str_contains((string)$column->getTypeSize(), ',')) {
                $size = '(' . $column->getTypeSize() . ')';
            } elseif ($typeName === 'character varying' || $typeName === 'character') {
                $size = '(' . $column->getTypeSize() . ')';
            }
        }

        return $this->joinParts([
            $this->quoteIdentifier($column->getName()),
            strtoupper($typeName) . $size,
            !is_null($column->isNull()) ? ($column->isNull() ? 'NULL' : 'NOT NULL') : '',
            $this->defaultClause($column),
            $column->isPrimary() ? 'PRIMARY KEY' : '',
            $column->getExtra() ?? '',
        ]);
    }

    protected function defaultClause(Column $column): string
    {
        if ($column->isDefaultDefined() && $column->getDefault() === ColumnDefault::currentTimestamp) {
            return 'DEFAULT CURRENT_TIMESTAMP';
        }

        return parent::defaultClause($column);
    }

    public function insertReturningClause(): string
    {
        return ' RETURNING id';
    }

    public function lastInsertId(PDOStatement $statement, PDO $pdo): mixed
    {
        return $statement->fetch(PDO::FETCH_ASSOC)['id'] ?? null;
    }

    public function columnList(PDO $pdo, string $tableName): array
    {
        $sql = 'SELECT column_name AS "Field", data_type AS "Type", '
            . "CASE WHEN is_nullable = 'YES' THEN 'YES' ELSE 'NO' END AS \"Null\", "
            . "CASE WHEN column_default IS NOT NULL THEN column_default ELSE '' END AS \"Default\", "
            . "CASE WHEN column_name IN (SELECT column_name FROM information_schema.key_column_usage WHERE table_name = ?) THEN 'PRI' ELSE '' END AS \"Key\", "
            . "'' AS \"Extra\" "
            . 'FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position;';

        return $this->run($pdo, $sql, [$tableName, $tableName])->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tableExists(PDO $pdo, string $tableName): bool
    {
        $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ? LIMIT 1;";

        return $this->run($pdo, $sql, [$tableName])->fetchColumn() >= 1;
    }

    public function listTables(PDO $pdo): array
    {
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";

        return array_column($this->fetchRows($this->run($pdo, $sql), PDO::FETCH_NUM), 0);
    }

    public function tableDetails(PDO $pdo): array
    {
        $sql = 'SELECT c.table_name, c.column_name, c.data_type, c.is_nullable, tc.constraint_type '
            . 'FROM information_schema.columns AS c '
            . 'LEFT JOIN information_schema.key_column_usage AS kcu ON c.table_name = kcu.table_name AND c.column_name = kcu.column_name '
            . 'LEFT JOIN information_schema.table_constraints AS tc ON kcu.constraint_name = tc.constraint_name '
            . "WHERE c.table_schema = 'public'";
        $details = [];

        foreach ($this->fetchRows($this->run($pdo, $sql)) as $row) {
            $details[$row['table_name']][] = $row;
        }

        return $details;
    }

    public function isColumnUnique(PDO $pdo, string $tableName, string $columnName): bool
    {
        $sql = 'SELECT COUNT(*) FROM pg_constraint c '
            . 'JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY(c.conkey) '
            . "WHERE c.conrelid = to_regclass(?) AND c.contype = 'u' AND a.attname = ?";

        return (int)$this->run($pdo, $sql, [$tableName, $columnName])->fetchColumn() > 0;
    }

}
