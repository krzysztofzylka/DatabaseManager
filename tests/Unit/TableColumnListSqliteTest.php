<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\Cache;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Table;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TableColumnListSqliteTest extends TestCase
{
    protected function setUp(): void
    {
        // columnList() caches per table name; avoid leaking state from other test files.
        Cache::clearAllCache();
    }

    /**
     * Regression test: columnList() used to run `pragma table_info("user")` for every
     * SQLite table regardless of its real name, so two different tables would get each
     * other's (or a nonexistent "user" table's) column description. It now queries the
     * table's actual, already-validated name.
     */
    public function testColumnListOnSqliteUsesTheActualTableNameNotAHardcodedOne(): void
    {
        $itemsStatement = $this->createStub(PDOStatement::class);
        $itemsStatement->method('fetchAll')->willReturn([
            ['name' => 'id', 'type' => 'INTEGER', 'notnull' => 1, 'dflt_value' => null, 'pk' => 1],
        ]);

        $categoriesStatement = $this->createStub(PDOStatement::class);
        $categoriesStatement->method('fetchAll')->willReturn([
            ['name' => 'label', 'type' => 'TEXT', 'notnull' => 0, 'dflt_value' => null, 'pk' => 0],
        ]);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('query')->willReturnCallback(function (string $sql) use ($itemsStatement, $categoriesStatement) {
            if (str_contains($sql, '"items"')) {
                return $itemsStatement;
            }

            if (str_contains($sql, '"categories"')) {
                return $categoriesStatement;
            }

            throw new RuntimeException('Unexpected PRAGMA query, table name was not substituted: ' . $sql);
        });

        $connect = DatabaseConnect::create()->setType(DatabaseType::sqlite)->setConnection($pdo);
        (new DatabaseManager())->connect($connect);

        $items = (new Table('items'))->columnList();
        $categories = (new Table('categories'))->columnList();

        $this->assertSame('id', $items[0]['Field']);
        $this->assertSame('label', $categories[0]['Field']);
    }
}
