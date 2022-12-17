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
    ->setExtra(); //extra value


$createTable = (new \DatabaseManager\Helper\CreateTable())
    ->setName('user') //table name
    ->addIdColumn() //predefined id column
    ->addColumn($customColumn); //add custom column
);

$createTable->execute(); //create table
```
## Predefined column
| Method                               | Description                                | Type              | 
|--------------------------------------|--------------------------------------------|-------------------|
| addIdColumn()                        | ID column with autoincrement (primary key) | INTEGER / INT(24) |
| addEmailColumn(bool $null = true)    | Email column                               | VARCHAR(255)      |
| addUsernameColumn(bool $null = true) | Username column                            | VARCHAR(255)      |
| addPasswordColumn(bool $null = true) | Password column                            | VARCHAR(255)      |
| addPhoneColumn(bool $null = true)    | Phone column                               | INT(26)           |

# Conditions
```php
use DatabaseManager\Condition;

$condition = (new Condition())
    ->where('user.id', '1');

$conditionNotAllowUser = (new Condition())
    ->orWhere(
        (new Condition())
        ->where(['user.blocked', 0])
        ->where(['user.disabled', 0])
    );
```