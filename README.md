Hi in DatabaseManager, see [Wiki](https://github.com/krzysztofzylka/DatabaseManager/wiki)!

# Required:
- PHP 8.1
- MySQL or SQLite

# Connect to database
```php
$databaseManager = new \krzysztofzylka\DatabaseManager\DatabaseManager();

try {
    $connect = \krzysztofzylka\DatabaseManager\DatabaseConnect::create()
        ->setType(\krzysztofzylka\DatabaseManager\Enum\DatabaseType::mysql)
        ->setDatabaseName('database')
        ->setUsername('username')
        ->setPassword('password');
    
    $databaseManager->connect($connect);
} catch (\krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException $exception) {
    die($exception->getHiddenMessage());
}
```

# Get table instance
```php
$table = new \krzysztofzylka\DatabaseManager\Table('table name')
```

# Query
```php
$table = new \krzysztofzylka\DatabaseManager\Table('table name');
var_dump($table->query('sql'));
```

# Find single data
```php
$table = new \krzysztofzylka\DatabaseManager\Table('table name');
var_dump(
    $table->find()
);
```

# Find all data
```php
$table = new \krzysztofzylka\DatabaseManager\Table('table name');
var_dump(
    $table->findAll()
);
```

# Find count
```php
$table = new \krzysztofzylka\DatabaseManager\Table('table name');
var_dump(
    $table->findCount()
);
```

# Find isset
```php
$table = new \krzysztofzylka\DatabaseManager\Table('table name');
var_dump(
    $table->findIsset()
);
```

# Insert
```php
$table = new \krzysztofzylka\DatabaseManager\Table('table name');
$table->insert([
    'column' => 'value'
])
```

# Update
```php
$table = new \krzysztofzylka\DatabaseManager\Table('table name');
$table->setId('element id')->update([
    'column' => 'new value'
])
```

# Update single column value
```php
$table = new \krzysztofzylka\DatabaseManager\Table('table name');
$table->setId('element id')->updateValue('column', 'new value');
```

# Conditions
## Simple array
```php
$conditions = [
    'column' => 'value',
    'column2' => 'value'
];
```
## Extended array
```php
$conditions = [
    'column' => 'value',
    new \krzysztofzylka\DatabaseManager\Condition('column', '>', 5),
    new \krzysztofzylka\DatabaseManager\Condition('column', 'LIKE', '%value%')
];
```