<?php

use krzysztofzylka\DatabaseManager\Table;

include('_connect.php');

    $table = new Table('testing');

    try {
        $faker = Faker\Factory::create();

        for ($i = 0; $i <= 99; $i++) {
            $table->save([
                'name' => $faker->userName(),
                'value' => $faker->text()
            ]);
        }
    } catch (Exception $exception) {
        var_dump($exception);
    }