<pre>
<?php

use DatabaseManager\DatabaseManager;
use DatabaseManager\Enum\BindType;
use DatabaseManager\Enum\DatabaseType;
use DatabaseManager\Helper\CreateTable;
use DatabaseManager\Helper\GetTable;

include('../../vendor/autoload.php');

$databaseManager = new DatabaseManager();
$databaseManager->connect(
    (new \DatabaseManager\DatabaseConnect())
        ->setType(DatabaseType::sqlite)
);

#create table
//$createTable = (new CreateTable())
//    ->setName('user')
//    ->addIdColumn()
//    ->addUsernameColumn()
//    ->addPasswordColumn()
//    ->execute();

#select
$table = (new GetTable())->setName('user');
$findAll = $table->bind(BindType::leftJoin, 'user2', 'user.id', 'user2.id')->findAll();

var_dump($table, $findAll);