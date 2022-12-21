<?php

namespace DatabaseManager;

use DatabaseManager\Enum\DatabaseType;
use DatabaseManager\Exception\UpdateTableException;
use DatabaseManager\Helper\TableColumn;

class AlterTable {

    private ?string $name = null;
    private DatabaseManager $databaseManager;
    private array $sql = [];

    /**
     * Połączenie z bazą danych
     */
    public function __construct() {
        $this->databaseManager = new DatabaseManager();
    }

    /**
     * Get table name
     * @return ?string
     */
    public function getName() : ?string {
        return $this->name;
    }

    /**
     * Set table name
     * @param ?string $name
     * @return AlterTable
     */
    public function setName(?string $name) : self {
        $this->name = $name;

        return $this;
    }

    /**
     * @param TableColumn $tableColumn
     * @return AlterTable
     */
    public function addColumn(TableColumn $tableColumn) : self {
        $column = trim(
            '`' . $tableColumn->getName() . '` '
            . strtoupper($tableColumn->getType()->name)
            . ($tableColumn->getTypeSize() ? '(' . $tableColumn->getTypeSize() . ') ' : ' ')
            . ($tableColumn->isNull() ? 'NULL ' : 'NOT NULL ')
            . ($tableColumn->isDefaultDefined()
                ? ('DEFAULT '
                    . (is_string($tableColumn->getDefault())
                        ? (str_contains($tableColumn->getDefault(), "'")
                            ? ('"' . $tableColumn->getDefault() . '"')
                            : ("'" . $tableColumn->getDefault() . "'"))
                        : $tableColumn->getDefault())
                    . ' ')
                : '')
            . (($tableColumn->isAutoincrement() && DatabaseManager::getDatabaseType() === DatabaseType::mysql) ? 'AUTO_INCREMENT ' : ' ')
            . (DatabaseManager::getDatabaseType() === DatabaseType::sqlite && $tableColumn->isPrimary() ? 'PRIMARY KEY ' : ' ')
            . ($tableColumn->getExtra())
        );

        if ($tableColumn->isPrimary() && DatabaseManager::getDatabaseType() === DatabaseType::mysql) {
            $this->primary[] = 'PRIMARY KEY (' . $tableColumn->getName() . ')';
        }

        $this->sql[] = 'ALTER TABLE `' . $this->getName() . '` ADD ' . $column;

        return $this;
    }

    /**
     * Execute update table
     * @return void
     * @throws UpdateTableException
     */
    public function execute() : void {
        try {
            var_dump($this->sql);
            foreach ($this->sql as $sql) {
                $this->databaseManager->query($sql);
            }
        } catch (\Exception $exception) {
            var_dump($exception);
            throw new UpdateTableException($exception->getMessage());
        }
    }

}