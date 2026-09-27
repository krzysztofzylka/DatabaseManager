<?php

namespace Tests\Unit\Dialect;

use krzysztofzylka\DatabaseManager\AlterTable;
use krzysztofzylka\DatabaseManager\Cache;
use krzysztofzylka\DatabaseManager\Column;
use krzysztofzylka\DatabaseManager\Columns\BoolColumn;
use krzysztofzylka\DatabaseManager\Columns\DateCreatedColumn;
use krzysztofzylka\DatabaseManager\Columns\DateModifyColumn;
use krzysztofzylka\DatabaseManager\Columns\DecimalColumn;
use krzysztofzylka\DatabaseManager\Columns\EnumColumn;
use krzysztofzylka\DatabaseManager\Columns\IdColumn;
use krzysztofzylka\DatabaseManager\Columns\IntColumn;
use krzysztofzylka\DatabaseManager\Columns\JsonColumn;
use krzysztofzylka\DatabaseManager\Columns\TextColumn;
use krzysztofzylka\DatabaseManager\Columns\VarcharColumn;
use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\ConnectionManager;
use krzysztofzylka\DatabaseManager\CreateIndex;
use krzysztofzylka\DatabaseManager\CreateTable;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Enum\SortDirection;
use krzysztofzylka\DatabaseManager\GroupBy;
use krzysztofzylka\DatabaseManager\OrderBy;
use krzysztofzylka\DatabaseManager\Table;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Pins the exact SQL generated for every engine, so moving engine-specific
 * logic between classes cannot silently change the queries.
 *
 * Regenerate after an intentional change with: UPDATE_SNAPSHOTS=1 vendor/bin/phpunit
 */
class DialectSnapshotTest extends TestCase
{

    public static function databaseTypes(): array
    {
        return [
            'mysql' => [DatabaseType::mysql],
            'sqlite' => [DatabaseType::sqlite],
            'postgres' => [DatabaseType::postgres],
        ];
    }

    protected function setUp(): void
    {
        Cache::clearAllCache();
        $connections = new ReflectionProperty(ConnectionManager::class, 'connections');
        $connections->setValue(null, []);
        $default = new ReflectionProperty(ConnectionManager::class, 'defaultConnection');
        $default->setValue(null, null);
    }

    #[DataProvider('databaseTypes')]
    public function testGeneratedSqlMatchesSnapshot(DatabaseType $type): void
    {
        $actual = $this->runScenarios($type);
        $file = __DIR__ . '/snapshots/' . $type->name . '.php';

        if (getenv('UPDATE_SNAPSHOTS')) {
            file_put_contents($file, "<?php\n\nreturn " . var_export($actual, true) . ";\n");
        }

        $this->assertSame(require $file, $actual);
    }

    private function runScenarios(DatabaseType $type): array
    {
        $pdo = $this->connect($type);
        $scenarios = [
            'createTable' => function () {
                (new CreateTable('users'))
                    ->addColumn(new IdColumn())
                    ->addColumn(new VarcharColumn('name', 100, false))
                    ->addColumn(new IntColumn('age', true, true))
                    ->addColumn(new DecimalColumn('price', '10,2', 1.5))
                    ->addColumn(new TextColumn('bio', "it's"))
                    ->addColumn(new BoolColumn('active', true))
                    ->addColumn(new EnumColumn('status', ['a', 'b'], 'a'))
                    ->addColumn(new JsonColumn('meta'))
                    ->addColumn(new DateCreatedColumn())
                    ->addColumn(new DateModifyColumn())
                    ->addColumn((new Column('uid', ColumnType::varchar, 36))->setDefault(null))
                    ->execute();
            },
            'createTableBigintAutoincrement' => function () {
                (new CreateTable('logs'))
                    ->addColumn((new Column('id', ColumnType::bigint, null))->setAutoincrement(true)->setPrimary(true))
                    ->addColumn((new Column('code', ColumnType::char, 3))->setDefault('abc'))
                    ->execute();
            },
            'insert' => fn () => (new Table('users'))->insert(['name' => 'john', 'age' => 3]),
            'update' => fn () => (new Table('users'))->setId(5)->update(['name' => 'jane', 'age' => 4]),
            'updateValue' => fn () => (new Table('users'))->setId(5)->updateValue('name', 'jim'),
            'delete' => fn () => (new Table('users'))->delete(5),
            'deleteByConditions' => fn () => (new Table('users'))->deleteByConditions(['name' => 'x', 'users.age' => ['>', 3]]),
            'find' => fn () => (new Table('users'))->find(
                [
                    'users.age' => ['>=', 18],
                    'status' => ['a', 'b'],
                    'created' => ['BETWEEN', ['2020-01-01', '2021-01-01']],
                    'OR' => ['name' => 'john', 'bio' => 'x'],
                    new Condition('users.bio', 'IS NOT', null),
                    new Condition('age', 'NOT IN', [1, 2]),
                ],
                null,
                'name DESC'
            ),
            'findCustomColumns' => fn () => (new Table('users'))->find(['id' => 1], ['id', 'users.name']),
            'findAll' => fn () => (new Table('users'))->findAll(
                ['active' => 1],
                null,
                [OrderBy::column('users.name', SortDirection::desc), OrderBy::column('age')],
                10,
                GroupBy::column('status'),
                5
            ),
            'findAllRawOrderWithFunction' => fn () => (new Table('users'))->findAll(null, ['id'], 'COALESCE(users.age, 0) ASC, name', '3'),
            'findAllJoin' => function () {
                (new Table('users'))
                    ->bind(BindType::leftJoin, 'posts', null, null, ['published' => 1])
                    ->bind(BindType::innerJoin, 'roles', 'users.role_id', 'r.id', new Condition('r.active', '=', 1), 'r')
                    ->findAll(['users.id' => 1]);
            },
            'findCount' => fn () => (new Table('users'))->findCount(['active' => 1]),
            'findCountGrouped' => fn () => (new Table('users'))->findCount(['active' => 1], 'status'),
            'findIsset' => fn () => (new Table('users'))->findIsset(['name' => 'john']),
            'columnList' => fn () => (new Table('users'))->columnList(),
            'exists' => fn () => (new Table('users'))->exists(),
            'getTables' => fn () => DatabaseManager::getTables(),
            'createIndex' => fn () => CreateIndex::create('users')->addColumn('name')->addColumn('age')->execute(),
            'alterTable' => function () {
                AlterTable::create('users')
                    ->addColumn(new VarcharColumn('nick', 50), 'name')
                    ->removeColumn('bio')
                    ->modifyColumn('age', ColumnType::int, 11, ['NOT NULL'])
                    ->renameColumn('nick', 'nickname')
                    ->execute();
            },
        ];

        if ($type !== DatabaseType::sqlite) {
            // SQLite's table details use a different row shape, which isColumnUnique() cannot read yet
            $scenarios['isColumnUnique'] = fn () => (new Table('users'))->isColumnUnique('name');
        }

        $result = [];

        foreach ($scenarios as $name => $scenario) {
            Cache::clearAllCache();
            $pdo->log = [];
            $scenario();
            $result[$name] = $pdo->log;
        }

        return $result;
    }

    private function connect(DatabaseType $type): RecordingPdo
    {
        $pdo = new RecordingPdo($type);
        (new DatabaseManager())->connect(DatabaseConnect::create()->setType($type)->setConnection($pdo));

        return $pdo;
    }

}

/**
 * PDO that records every statement instead of running it, answering with canned rows
 */
class RecordingPdo extends PDO
{

    public array $log = [];

    public function __construct(private readonly DatabaseType $type)
    {
        parent::__construct('sqlite::memory:');
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->log[] = $query;

        return new FakeStatement($this->rowsFor($query), $this);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $this->log[] = $query;

        return new FakeStatement($this->rowsFor($query), $this);
    }

    public function exec(string $statement): int|false
    {
        $this->log[] = $statement;

        return 1;
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return '7';
    }

    private function rowsFor(string $sql): array
    {
        $columnListQuery = str_starts_with($sql, 'pragma table_info')
            || str_starts_with($sql, 'DESCRIBE')
            || str_contains($sql, 'ordinal_position');

        if ($columnListQuery && $this->type === DatabaseType::sqlite) {
            return [
                ['name' => 'id', 'type' => 'INTEGER', 'notnull' => 1, 'pk' => 1, 'dflt_value' => null],
                ['name' => 'name', 'type' => 'VARCHAR(100)', 'notnull' => 1, 'pk' => 0, 'dflt_value' => null],
                ['name' => 'status', 'type' => "enum('a','b')", 'notnull' => 0, 'pk' => 0, 'dflt_value' => null],
            ];
        }

        if ($columnListQuery) {
            return [
                ['Field' => 'id', 'Type' => 'bigint', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => ''],
                ['Field' => 'name', 'Type' => 'varchar(100)', 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => ''],
                ['Field' => 'status', 'Type' => "enum('a','b')", 'Null' => 'YES', 'Key' => '', 'Default' => null, 'Extra' => ''],
            ];
        }

        if (str_contains($sql, 'information_schema.COLUMNS')) {
            return [['TABLE_NAME' => 'users', 'COLUMN_NAME' => 'name', 'COLUMN_KEY' => 'UNI']];
        }

        if ($sql === 'SHOW TABLES' || str_contains($sql, "sqlite_master WHERE type='table'") || str_ends_with($sql, "table_schema = 'public'")) {
            return [['users']];
        }

        if (str_contains($sql, 'RETURNING')) {
            return [['id' => 7]];
        }

        if (str_contains($sql, 'COUNT(')) {
            return [['count' => 3]];
        }

        return [];
    }

}

/**
 * Statement serving canned rows and recording the values bound to it
 */
class FakeStatement extends PDOStatement
{

    private array $bound = [];

    public function __construct(private array $rows, private readonly RecordingPdo $pdo)
    {
    }

    public function bindValue(int|string $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        $this->bound[$param] = $value;

        return true;
    }

    public function execute(?array $params = null): bool
    {
        $bound = $params ?? $this->bound;

        if (!empty($bound)) {
            $this->pdo->log[] = 'bind: ' . json_encode($bound);
        }

        return true;
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        return array_shift($this->rows) ?? false;
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        $rows = $this->rows;
        $this->rows = [];

        return $rows;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        $row = array_shift($this->rows);

        return $row === null ? false : array_values($row)[$column];
    }

}
