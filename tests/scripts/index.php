<pre>
<?php
include('../../vendor/autoload.php');

$databaseManager = new \DatabaseManager\DatabaseManager();
$databaseManager->connect(
    (new \DatabaseManager\DatabaseConnect())
        ->setType(\DatabaseManager\Enum\DatabaseType::sqlite)
);
$createTable = new \DatabaseManager\Helper\CreateTable();
$createTable->setName('user');
$createTable->addIdColumn();
$createTable->addColumn(
    (new \DatabaseManager\Helper\TableColumn())->setName('asd')->setNull(false)->setAutoincrement(true)->setType(\DatabaseManager\Enum\ColumnType::varchar, 255)->setDefault('as')
);

$createTable->execute();