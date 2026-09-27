<?php

namespace krzysztofzylka\DatabaseManager\Dialect;

use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\Enum\Trigger;
use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use PDO;
use PDOException;

class MysqlDialect extends AbstractDialect
{

    public function createPdo(DatabaseConnect $connect): PDO
    {
        try {
            return new PDO(
                'mysql:host=' . $connect->getHost()
                . ';dbname=' . $connect->getDatabaseName()
                . ';charset=' . $connect->getCharset()
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
        return '`';
    }

    public function columnDefinition(Column $column): string
    {
        return $this->joinParts([
            $this->quoteIdentifier($column->getName()),
            strtoupper($column->getType()->name) . ($column->getTypeSize() ? '(' . $column->getTypeSize() . ')' : ''),
            $column->isUnsigned() ? 'UNSIGNED' : '',
            !is_null($column->isNull()) ? ($column->isNull() ? 'NULL' : 'NOT NULL') : '',
            $this->defaultClause($column),
            $column->isAutoincrement() ? 'AUTO_INCREMENT' : '',
            $column->getExtra() ?? '',
        ]);
    }

    public function primaryKeyClause(Column $column): ?string
    {
        return $column->isPrimary() ? 'PRIMARY KEY (' . $this->quoteIdentifier($column->getName()) . ')' : null;
    }

    public function triggerSql(Trigger $trigger, string $tableName, string $columnName): ?string
    {
        return $trigger->generate($tableName, $columnName) . ';';
    }

    public function columnList(PDO $pdo, string $tableName): array
    {
        return $this->run($pdo, 'DESCRIBE ' . $this->quoteIdentifier($tableName) . ';')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tableExists(PDO $pdo, string $tableName): bool
    {
        $sql = 'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1;';

        return $this->run($pdo, $sql, [$tableName])->fetchColumn() >= 1;
    }

    public function listTables(PDO $pdo): array
    {
        return array_column($this->fetchRows($this->run($pdo, 'SHOW TABLES'), PDO::FETCH_NUM), 0);
    }

    public function tableDetails(PDO $pdo): array
    {
        $sql = 'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA '
            . 'FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()';
        $details = [];

        foreach ($this->fetchRows($this->run($pdo, $sql)) as $row) {
            $details[$row['TABLE_NAME']][] = $row;
        }

        return $details;
    }

    public function isColumnUnique(PDO $pdo, string $tableName, string $columnName): bool
    {
        $sql = 'SELECT COUNT(*) FROM information_schema.COLUMNS '
            . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND COLUMN_KEY = 'UNI'";

        return (int)$this->run($pdo, $sql, [$tableName, $columnName])->fetchColumn() > 0;
    }

}
