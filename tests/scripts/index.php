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
$createTable->addEmailColumn(false);

$createTable->execute();