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

#create table
//$createTable = (new CreateTable())
//    ->setName('user')
//    ->addIdColumn()
//    ->addUsernameColumn()
//    ->addPasswordColumn()
//    ->execute();

#select
//$condition = (new Condition())
//    ->where('user.id', '2')
//    ->where('user2.password', 'p22');
$table = (new GetTable())->setName('user');
//$findAll = $table
//    ->bind(BindType::leftJoin, 'user2', 'user.id', 'user2.id')
//    ->findAll($condition);

//$find = $table->find();

//$table->insert([
//    'username' => 'test',
//    'password' => 'test'
//]);

//$table->setId(4);
//$table->update([
//        'username' => 'updaterusername',
//    'password' => 'updatepassword'
//]);
//$table->delete(7);
//var_dump($table);
//var_dump($table->findCount((new Condition())->where('id', 15, '>')));
$table->setId(8)->updateValue('username', 'xd');