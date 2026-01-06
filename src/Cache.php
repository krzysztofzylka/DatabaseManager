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

    /**
     * Clear cache for a specific table
     * @param string $tableName
     * @return void
     */
    public static function clearTableCache(string $tableName): void
    {
        unset(self::$data['columnList_' . $tableName]);
    }

    /**
     * Clear all cache
     * @return void
     */
    public static function clearAllCache(): void
    {
        self::$data = [];
    }

}