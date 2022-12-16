# Connect to database
## MySQL
```php
$databaseConnect = (new \DatabaseManager\DatabaseConnect())
        ->setType(\DatabaseManager\Enum\DatabaseType::mysql) //type as mysql
        ->setDatabaseName('name') //database name
        ->setHost('127.0.0.1') //database host (default 127.0.0.1)
        ->setUsername('username') //database username
        ->setPassword('password'); //database password

$databaseManager = new \DatabaseManager\DatabaseManager();
$databaseManager->connect($databaseConnect);
```
## SQLite
```php
$databaseConnect = (new \DatabaseManager\DatabaseConnect())
        ->setType(\DatabaseManager\Enum\DatabaseType::sqlite) //type as sqlite
        ->setSqlitePath('database.sqlite'); //sqlite path (default database.sqlite)

$databaseManager = new \DatabaseManager\DatabaseManager();
$databaseManager->connect($databaseConnect);
```

# Create table
```php
$customColumn = (new \DatabaseManager\Helper\TableColumn())
    ->setName('name') //column name
    ->setNull(false) //null (true), not null (false) (default false)
    ->setAutoincrement(false) //is autoincrement
    ->setType(\DatabaseManager\Enum\ColumnType::varchar, 255) //table type with length
    ->setDefault() //default value (default empty)
    ->setPrimary(false) //is primary (default false)
    ->setExtra() //extra value


$createTable = (new \DatabaseManager\Helper\CreateTable())
    ->setName('user') //table name
    ->addIdColumn() //predefined id column
    ->addColumn($customColumn) //add custom column
);

$createTable->execute(); //create table
```