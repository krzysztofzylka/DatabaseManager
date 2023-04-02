<?php

require('_init.php');

$table = new \krzysztofzylka\DatabaseManager\Table('user');

echo '<pre>';
var_dump($table->query('SELECT * FROM user'));
echo '</pre>';

require('_end.php');