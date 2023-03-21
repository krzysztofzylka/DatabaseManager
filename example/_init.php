<?php

include('../vendor/autoload.php');

$databaseManager = new \krzysztofzylka\DatabaseManager\DatabaseManager();
try {
    $databaseManager->connect(
        (new \krzysztofzylka\DatabaseManager\DatabaseConnect())
            ->setType(\krzysztofzylka\DatabaseManager\Enum\DatabaseType::mysql)
            ->setDatabaseName('databasemanager')
            ->setUsername('root')
            ->setPassword('root')
            ->setDebug(true)
    );
} catch (\krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException $exception) {
    die($exception->getHiddenMessage());
}

?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

<a href="/" class="btn btn-primary mt-2 ms-2">Back to menu</a>