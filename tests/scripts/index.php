<pre>
<?php
include('../../vendor/autoload.php');

$databaseManager = new \DatabaseManager\DatabaseManager();
$databaseManager->connect(
    (new \DatabaseManager\DatabaseConnect())
        ->setType(\DatabaseManager\Enum\DatabaseType::sqlite)
);

#create table
//$createTable = (new \DatabaseManager\Helper\CreateTable())
//    ->setName('user')
//    ->addIdColumn()
//    ->addUsernameColumn()
//    ->addPasswordColumn()
//    ->execute();

#select
$table = (new \DatabaseManager\Helper\GetTable())->setName('user');
$findAll = $table->findAll();

var_dump($table, $findAll);