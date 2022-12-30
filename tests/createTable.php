<h1>Create test table</h1>

<?php

require('_init.php');

try {
    $createTable = (new \krzysztofzylka\DatabaseManager\CreateTable())
        ->setName('user_test')
        ->addIdColumn()
        ->addUsernameColumn()
        ->addEmailColumn()
        ->addPasswordColumn()
        ->addDateCreatedColumn()
        ->addDateModifyColumn()
        ->execute();
} catch (\krzysztofzylka\DatabaseManager\Exception\CreateTableException $e) {
    var_dump($e->getHiddenMessage());
}

require('_end.php');