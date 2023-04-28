<?php

use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Table;

require('_init.php');

echo '<h1>findCount</h1>';

try {
    $table = new Table();
    $table->setName('user');
    $table->bind(BindType::hasOne, 'user_permission');
    echo '<pre>';
    var_dump($table->findCount(['user_permission.id' => 2]));
    echo '</pre>';
} catch (\krzysztofzylka\DatabaseManager\Exception\DatabaseException $exception) {
    echo $exception->getHiddenMessage();
}

echo '<h1>findIsset</h1>';

try {
    $table = new Table();
    $table->setName('user');
    $table->bind(BindType::hasOne, 'user_permission');
    echo '<pre>';
    var_dump($table->findIsset(['user_permission.id' => 2]));
    echo '</pre>';
} catch (\krzysztofzylka\DatabaseManager\Exception\DatabaseException $exception) {
    echo $exception->getHiddenMessage();
}

require('_end.php');