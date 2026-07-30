<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsStableWithinSession(): void
    {
        $first = Csrf::token();
        $second = Csrf::token();

        self::assertSame($first, $second);
    }

    public function testTokenIsNonEmptyHexString(): void
    {
        $token = Csrf::token();

        self::assertSame(64, strlen($token));
        self::assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testVerifyAcceptsCorrectToken(): void
    {
        $token = Csrf::token();

        self::assertTrue(Csrf::verify($token));
    }

    public function testVerifyRejectsIncorrectToken(): void
    {
        Csrf::token();

        self::assertFalse(Csrf::verify('wrong-token'));
    }

    public function testVerifyRejectsNull(): void
    {
        Csrf::token();

        self::assertFalse(Csrf::verify(null));
    }

    public function testVerifyRejectsEmptyString(): void
    {
        Csrf::token();

        self::assertFalse(Csrf::verify(''));
    }

    public function testVerifyRejectsWhenNoTokenGenerated(): void
    {
        self::assertFalse(Csrf::verify('anything'));
    }

    public function testFieldContainsToken(): void
    {
        $token = Csrf::token();
        $field = Csrf::field();

        self::assertStringContainsString('type="hidden"', $field);
        self::assertStringContainsString('name="_csrf"', $field);
        self::assertStringContainsString($token, $field);
    }
}
