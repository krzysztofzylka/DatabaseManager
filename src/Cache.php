<?php

namespace krzysztofzylka\DatabaseManager;

class Cache
{

    /**
     * Data
     * @var array
     */
    private static array $data = [];

    public static function getData(string $name): mixed
    {
        return self::$data[$name] ?? null;
    }

    public static function saveData(string $name, mixed $value): void
    {
        self::$data[$name] = $value;
    }

}