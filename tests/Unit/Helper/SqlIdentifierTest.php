<?php

namespace Tests\Unit\Helper;

use InvalidArgumentException;
use krzysztofzylka\DatabaseManager\Helper\SqlIdentifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SqlIdentifierTest extends TestCase
{
    public function testAcceptsValidIdentifiers(): void
    {
        $this->assertSame('users', SqlIdentifier::validate('users'));
        $this->assertSame('_private', SqlIdentifier::validate('_private'));
        $this->assertSame('column_2', SqlIdentifier::validate('column_2'));
    }

    public static function invalidIdentifierProvider(): array
    {
        return [
            'space' => ['users table'],
            'semicolon' => ['users;'],
            'line comment' => ['users -- comment'],
            'block comment' => ['users /* comment */'],
            'single quote' => ["users'"],
            'double quote' => ['users"'],
            'backtick' => ['users`'],
            'parenthesis' => ['users()'],
            'empty string' => [''],
            'starts with digit' => ['1users'],
            'dot (use validateQualified instead)' => ['users.id'],
        ];
    }

    #[DataProvider('invalidIdentifierProvider')]
    public function testRejectsInvalidIdentifiers(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);

        SqlIdentifier::validate($name);
    }

    public function testValidateQualifiedAcceptsTableDotColumn(): void
    {
        $this->assertSame('users.email', SqlIdentifier::validateQualified('users.email'));
    }

    public function testValidateQualifiedAcceptsUnqualifiedName(): void
    {
        $this->assertSame('email', SqlIdentifier::validateQualified('email'));
    }

    public static function invalidQualifiedIdentifierProvider(): array
    {
        return [
            'trailing dot / empty column segment' => ['users.'],
            'leading dot / empty table segment' => ['.email'],
            'double dot / empty middle segment' => ['users..email'],
            'malicious segment' => ["users.email' OR '1'='1"],
            'sql comment in segment' => ['users.-- comment'],
        ];
    }

    #[DataProvider('invalidQualifiedIdentifierProvider')]
    public function testValidateQualifiedRejectsInvalidSegments(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);

        SqlIdentifier::validateQualified($name);
    }
}
