# Connect to database
## MySQL
```php
$databaseConnect = (new \DatabaseManager\DatabaseConnect())
        ->setType(\DatabaseManager\Enum\DatabaseType::mysql) //type as mysql
        ->setDatabaseName('name') //database name
        ->setCharset('utf8') //set database charset (default utf8)
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

# Custom query
```php
$databaseManager = new DatabaseManager();

# database connect

$databaseManager->query('sql');
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


$createTable = (new \DatabaseManager\CreateTable())
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

# Alter table
## Add column

```php
$customColumn = (new \DatabaseManager\Helper\TableColumn())
    ->setName('permission') //column name
    ->setNull(false) //null (true), not null (false) (default false)
    ->setAutoincrement(false) //is autoincrement
    ->setType(\DatabaseManager\Enum\ColumnType::varchar, 255) //table type with length
    ->setDefault() //default value (default empty)
    ->setPrimary(false) //is primary (default false)
    ->setExtra(); //extra value


$updateTable = (new \DatabaseManager\AlterTable())
    ->setName('user') //table name
    ->addColumn($customColumn); //add custom column
);

$updateTable->execute(); //create table
```

# Conditions
```php
use DatabaseManager\Condition;

$condition = (new Condition())
    ->where('user.id', '1');

$condition2 = (new Condition())
    ->where('user.id', '3', '>=');

$conditionNotAllowUser = (new Condition())
    ->orWhere(
        (new Condition())
        ->where(['user.blocked', 0])
        ->where(['user.disabled', 0])
    );
```

# Table
## Get table
```php
$table = (new GetTable())->setName('table_name');
```
## Select
### Find all
```php
use DatabaseManager\Condition;

$table = (new GetTable())->setName('table_name');

$conditions = new Condition(); //conditions not required
$find = $table->findAll($conditions);

/**
 * example findAll
 * [
 *   0 => [
 *     'table_name' => [
 *       'id' => 1,
 *       'name' => 'string'
 *     ]
 *   ]
 * ]
 */
```
### Find
```php
use DatabaseManager\Condition;

$table = (new GetTable())->setName('table_name');

$conditions = new Condition(); //conditions not required
$find = $table->find($conditions);

/**
 * example find
 * [
 *   'table_name' => [
 *     'id' => 1,
 *     'name' => 'string'
 *   ]
 * ]
 */
```
### Find count
```php
use DatabaseManager\Condition;

$table = (new GetTable())->setName('table_name');

$condition = new Condition();
$count = $table->findCount($condition); // int, condition is not required
```
### Find isset
```php
use DatabaseManager\Condition;

$table = (new GetTable())->setName('table_name');

$condition = (new Condition())->where('login', 'admin'); //isset user in database
$isset = $table->findCount($condition); // bool, condition is not required
```
## Insert
```php
use DatabaseManager\Condition;

$table = (new GetTable())->setName('table_name');

$table->insert([
    'column1' => 'value1',
    'column2' => 'value2'
]); //return true or false

$insertId = $table->getId(); //insert ID
```
## Set/Get id
```php
use DatabaseManager\Condition;

$table = (new GetTable())->setName('table_name');

$id = $table->getId(); //get id (insert)
$table->setId('int'); //set id (update)
```
## Update
### Standard
```php
use DatabaseManager\Condition;

$table = (new GetTable())->setName('table_name')->setId(1);

$table->update([
    'column1' => 'updated column1',
    'column2' => 'updated column2'
]); //return true or false
```
### Update single column
```php
use DatabaseManager\Condition;

$table = (new GetTable())->setName('table_name')->setId(1);

$table->updateValue('column1', 'new value'); //return bool
```
## Delete
```php
use DatabaseManager\Condition;

$table = (new GetTable())->setName('table_name');

$table->setId(2)->delete(); // delete item id=2
$table->delete(6); //delete item id=6
```

# Transactions
```php
$transaction = new \DatabaseManager\Transaction();

$transaction->begin();

try {
    #sql
    
    $transaction->commit();
} catch (Exception) {
    $transaction->rollback();
}
```