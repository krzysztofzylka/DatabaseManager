<pre>
<?php

use DatabaseManager\Condition;
use DatabaseManager\DatabaseManager;
use DatabaseManager\Enum\BindType;
use DatabaseManager\Enum\DatabaseType;
use DatabaseManager\CreateTable;
use DatabaseManager\GetTable;

include('../../vendor/autoload.php');

$databaseManager = new DatabaseManager();
$databaseManager->connect(
    (new \DatabaseManager\DatabaseConnect())
        ->setType(DatabaseType::sqlite)
);

$table = (new GetTable())->setName('user');
$table->setId(8)->updateValue('username', 'xd');