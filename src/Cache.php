<?php

namespace krzysztofzylka\DatabaseManager;

class Cache
{

    /**
     * Data
     * @var array
     */
    private static array $data = [];

    /**
     * Get data from cache
     * @param string $name
     * @return mixed
     */
    public static function getData(string $name): mixed
    {
        return self::$data[$name] ?? null;
    }

    /**
     * Save data to cache
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function saveData(string $name, mixed $value): void
    {
        self::$data[$name] = $value;
    }

}