<?php

namespace Tests\Unit;

use krzysztofzylka\DatabaseManager\Cache;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset cache data przed każdym testem
        $reflection = new ReflectionClass(Cache::class);
        $property = $reflection->getProperty('data');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    public function testSaveAndGetData(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        Cache::saveData($key, $value);
        $result = Cache::getData($key);

        $this->assertEquals($value, $result);
    }

    public function testGetNonExistentData(): void
    {
        $result = Cache::getData('non_existent_key');
        $this->assertNull($result);
    }

    public function testOverwriteData(): void
    {
        $key = 'overwrite_key';

        Cache::saveData($key, 'first_value');
        Cache::saveData($key, 'second_value');

        $result = Cache::getData($key);
        $this->assertEquals('second_value', $result);
    }

    public function testComplexData(): void
    {
        $key = 'complex_data';
        $value = [
            'id' => 1,
            'name' => 'Test',
            'nested' => [
                'key' => 'value'
            ]
        ];

        Cache::saveData($key, $value);
        $result = Cache::getData($key);

        $this->assertEquals($value, $result);
    }

    public function testMultipleEntries(): void
    {
        Cache::saveData('key1', 'value1');
        Cache::saveData('key2', 'value2');
        Cache::saveData('key3', 'value3');

        $this->assertEquals('value1', Cache::getData('key1'));
        $this->assertEquals('value2', Cache::getData('key2'));
        $this->assertEquals('value3', Cache::getData('key3'));
    }
}