<h1>Join</h1>
<pre>
<?php

use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Table;

require('_init.php');

$table = new Table();
$table->setName('user');
$table->bind(BindType::hasOne, 'user_permission');
var_dump($table->findAll());

require('_end.php');