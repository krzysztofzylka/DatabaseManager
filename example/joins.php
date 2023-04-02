<?php

use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Table;

require('_init.php');

echo '<h1>Join</h1>';

try {
    $table = new Table();
    $table->setName('user');
    $table->bind(BindType::hasOne, 'user_permission');
    echo '<pre>';
    var_dump($table->findAll(null, null, 'user.id ASC, user_permission.id ASC'));
    echo '</pre>';
} catch (Exception) {}

require('_end.php');