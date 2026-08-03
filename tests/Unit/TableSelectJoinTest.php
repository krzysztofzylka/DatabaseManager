<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Table;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class TableSelectJoinTest extends TestCase
{
    private function connectWithMockPdo(PDOStatement $statement, DatabaseType $type = DatabaseType::mysql): PDO
    {
        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $connect = DatabaseConnect::create()
            ->setType($type)
            ->setConnection($pdo);

        (new DatabaseManager())->connect($connect);

        return $pdo;
    }

    public function testFindAllWithInnerJoinBuildsJoinSqlAndNestsData(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([
            ['items.id' => 1, 'items.name' => 'Widget', 'categories.name' => 'Tools'],
        ]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');
        $table->bind(BindType::innerJoin, 'categories');

        $result = $table->findAll(null, ['id', 'name', 'categories.name']);

        $this->assertStringContainsString(
            'INNER JOIN `categories` ON `items`.`id` = `categories`.`items_id`',
            DatabaseManager::getLastSql()
        );
        $this->assertSame([
            ['items' => ['id' => 1, 'name' => 'Widget'], 'categories' => ['name' => 'Tools']],
        ], $result);
    }

    public function testFindAllWithHasOneDeduplicatesRepeatedPrimaryKey(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([
            ['items.id' => 1, 'items.name' => 'Widget', 'profile.bio' => 'A widget'],
            ['items.id' => 1, 'items.name' => 'Widget', 'profile.bio' => 'A widget'],
            ['items.id' => 2, 'items.name' => 'Gadget', 'profile.bio' => 'A gadget'],
        ]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');
        $table->bind(BindType::hasOne, 'profile');

        $result = $table->findAll(null, ['id', 'name', 'profile.bio']);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['items']['id']);
        $this->assertSame(2, $result[1]['items']['id']);
    }

    public function testFindAllWithHasManyGroupsRelatedRows(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([
            ['items.id' => 1, 'items.name' => 'Widget', 'tags.name' => 'red'],
            ['items.id' => 1, 'items.name' => 'Widget', 'tags.name' => 'blue'],
            ['items.id' => 2, 'items.name' => 'Gadget', 'tags.name' => 'green'],
        ]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');
        $table->bind(BindType::hasMany, 'tags');

        $result = $table->findAll(null, ['id', 'name', 'tags.name']);

        $this->assertSame([
            [
                'items' => ['id' => 1, 'name' => 'Widget'],
                'tags' => [['name' => 'red'], ['name' => 'blue']],
            ],
            [
                'items' => ['id' => 2, 'name' => 'Gadget'],
                'tags' => [['name' => 'green']],
            ],
        ], $result);
    }

    /**
     * Regression test for the Condition SQL injection fix: a Condition attached to a
     * JOIN via bind() must bind its value as a parameter, never embed it into the SQL
     * string, and its placeholder must not collide with the WHERE clause's placeholders.
     */
    public function testJoinConditionBindsValueInsteadOfEmbeddingIt(): void
    {
        $boundValues = [];

        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([]);
        $statement->method('bindValue')->willReturnCallback(function ($key, $value) use (&$boundValues) {
            $boundValues[$key] = $value;

            return true;
        });

        $this->connectWithMockPdo($statement);

        $table = new Table('items');
        $table->bind(
            BindType::innerJoin,
            'categories',
            condition: new Condition('categories.status', '=', "x' OR '1'='1")
        );

        $table->findAll(['name' => 'Widget'], ['id']);

        $sql = DatabaseManager::getLastSql();

        $this->assertStringNotContainsString("x' OR '1'='1", $sql);
        $this->assertStringContainsString(':bind_0', $sql);
        $this->assertStringContainsString(':bind_1', $sql);
        $this->assertSame('Widget', $boundValues[':bind_0']);
        $this->assertSame("x' OR '1'='1", $boundValues[':bind_1']);
    }

    public function testUnbindRemovesPreviouslyBoundTable(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([]);

        $this->connectWithMockPdo($statement);

        $table = new Table('items');
        $table->bind(BindType::innerJoin, 'categories');
        $table->unbind('categories');
        $table->findAll(null, ['id']);

        $this->assertStringNotContainsString('JOIN', DatabaseManager::getLastSql());
    }

    public function testFindAllJoinUsesDoubleQuotesForPostgres(): void
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([]);

        $this->connectWithMockPdo($statement, DatabaseType::postgres);

        $table = new Table('items');
        $table->bind(BindType::innerJoin, 'categories');
        $table->findAll(null, ['id', 'categories.name']);

        $this->assertStringContainsString(
            'INNER JOIN "categories" ON "items"."id" = "categories"."items_id"',
            DatabaseManager::getLastSql()
        );
    }
}
