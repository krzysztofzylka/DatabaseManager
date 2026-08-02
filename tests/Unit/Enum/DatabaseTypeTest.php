<?php

namespace Tests\Unit\Enum;

use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use PHPUnit\Framework\TestCase;

class DatabaseTypeTest extends TestCase
{
    public function testHasExpectedCases(): void
    {
        $names = array_map(static fn (DatabaseType $type) => $type->name, DatabaseType::cases());

        $this->assertCount(3, DatabaseType::cases());
        $this->assertContains('mysql', $names);
        $this->assertContains('sqlite', $names);
        $this->assertContains('postgres', $names);
    }
}
