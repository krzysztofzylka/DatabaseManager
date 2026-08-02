<?php

namespace Tests\Unit\Exception;

use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use PHPUnit\Framework\TestCase;

class DatabaseManagerExceptionTest extends TestCase
{
    public function testPublicMessageIsGeneric(): void
    {
        $exception = new DatabaseManagerException('SQLSTATE[42S02]: Table not found');

        $this->assertSame('Database error', $exception->getMessage());
    }

    public function testHiddenMessageRetainsOriginalDetail(): void
    {
        $exception = new DatabaseManagerException('Deadlock found when trying to get lock');

        $this->assertSame('Deadlock found when trying to get lock', $exception->getHiddenMessage());
    }

    public function testCodeAndPreviousArePreserved(): void
    {
        $previous = new \Exception('root cause');
        $exception = new DatabaseManagerException('wrapped', 42, $previous);

        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
