<?php

use krzysztofzylka\DatabaseManager\Table;

require('_init.php');

echo '<h1>Join</h1>';

try {
    $table = new Table('user');

    echo '<pre>';
    var_dump($table->find([
        'user.id' => 4,
    ]));
    var_dump($table->findAll([
        'OR' => [
            'id' => 5,
            ['id' => 6],
            ['id' => 15]
        ]
    ]));
    echo '</pre>';
} catch (Exception) {}

require('_end.php');