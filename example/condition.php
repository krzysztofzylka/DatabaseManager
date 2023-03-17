<?php

use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Helper\Where;

require('_init.php');

echo '<h1>Condition</h1>';
echo '<pre>';
print_r([
    (string)new Condition('table.column', '=', 'c'),
    (string)new Condition('test', '!=', '"c"'),
    (string)new Condition('test', '<>', "'c'"),
    (string)new Condition('test', 'IN', ['a', 'b', 'c'])
]);
echo '</pre>';

echo '<h1>Where test</h1>';
echo '<pre>';
print_r((new Where())->getPrepareConditions([
    'test' => 'value',
    'OR' => [
        'test2' => 'value2',
        'test3' => 'value3'
    ],
    new Condition('test4', 'IN', ['value4_1', 'value4_2', 'value4_3'])
]));
echo '</pre>';

require('_end.php');