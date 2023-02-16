<h1>Create test table</h1>

<?php

require('_init.php');

try {
    $faker = Faker\Factory::create();
    $table = new \krzysztofzylka\DatabaseManager\Table();
    $table->setName('user');

    if (!$table->exists()) {
        $createTable = (new \krzysztofzylka\DatabaseManager\CreateTable())
            ->setName('user')
            ->addIdColumn()
            ->addUsernameColumn()
            ->addEmailColumn()
            ->addPasswordColumn()
            ->addDateCreatedColumn()
            ->addDateModifyColumn()
            ->execute();

        for ($i = 1; $i <= 100; $i++) {
            $table->insert([
                    'username' => $faker->userName(),
                    'password' => md5($faker->password()),
                    'email' => $faker->email()
            ]);
        }
    }

    $table = new \krzysztofzylka\DatabaseManager\Table();
    $table->setName('user_permission');

    if (!$table->exists()) {
        $createTable = (new \krzysztofzylka\DatabaseManager\CreateTable())
            ->setName('user_permission')
            ->addIdColumn()
            ->addSimpleIntColumn('user_id')
            ->addSimpleIntColumn('permission_id')
            ->addDateCreatedColumn()
            ->addDateModifyColumn()
            ->execute();

        for ($i = 1; $i <= 2000; $i++) {
            $table->insert([
                'user_id' => rand(1, 100),
                'permission_id' => rand(1, 20)
            ]);
        }
    }
} catch (\krzysztofzylka\DatabaseManager\Exception\CreateTableException $e) {
    var_dump($e->getHiddenMessage());
}

require('_end.php');