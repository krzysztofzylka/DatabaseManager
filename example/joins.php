<h1>Join</h1>
<pre>
<?php

use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Table;

require('_init.php');

try {
    $table = new Table();
    $table->setName('user');
    $table->bind(BindType::hasOne, 'user_permission', null, null);
    var_dump($table->findAll(null, 'user.id ASC, user_permission.id ASC'));
} catch (Exception) {}

require('_end.php');