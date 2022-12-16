<pre>
<?php
include('../../vendor/autoload.php');

$databaseManager = new \DatabaseManager\DatabaseManager();
$databaseManager->connect(
    (new \DatabaseManager\DatabaseConnect())
        ->setType(\DatabaseManager\Enum\DatabaseType::sqlite)
);
