<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../vendor/autoload.php');

use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Exception\DatabaseException;

$databaseManager = new DatabaseManager();

try {
    $databaseManager->connect(
        (new DatabaseConnect())
            ->setConnection('databasemanager', 'user', 'password')
    );
} catch (DatabaseException $exception) {
    die($exception->getHiddenMessage());
}