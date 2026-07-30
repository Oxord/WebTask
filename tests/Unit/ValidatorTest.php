<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequiredPassesAndFails(): void
    {
        self::assertTrue(Validator::make(['name' => 'Anna'], ['name' => 'required'])->passes());
        self::assertTrue(Validator::make(['name' => ''], ['name' => 'required'])->fails());
        self::assertTrue(Validator::make([], ['name' => 'required'])->fails());
    }

    public function testEmailPassesAndFails(): void
    {
        self::assertTrue(Validator::make(['email' => 'test@example.com'], ['email' => 'email'])->passes());
        self::assertTrue(Validator::make(['email' => 'not-an-email'], ['email' => 'email'])->fails());
    }

    public function testMinForStringsChecksLength(): void
    {
        self::assertTrue(Validator::make(['name' => 'Ann'], ['name' => 'min:3'])->passes());
        self::assertTrue(Validator::make(['name' => 'An'], ['name' => 'min:3'])->fails());
    }

    public function testMaxForStringsChecksLength(): void
    {
        self::assertTrue(Validator::make(['name' => 'Ann'], ['name' => 'max:3'])->passes());
        self::assertTrue(Validator::make(['name' => 'Anna'], ['name' => 'max:3'])->fails());
    }

    public function testMinForNumbersChecksValue(): void
    {
        self::assertTrue(Validator::make(['age' => '18'], ['age' => 'numeric|min:18'])->passes());
        self::assertTrue(Validator::make(['age' => '17'], ['age' => 'numeric|min:18'])->fails());
    }

    public function testMaxForNumbersChecksValue(): void
    {
        self::assertTrue(Validator::make(['age' => '5'], ['age' => 'int|max:5'])->passes());
        self::assertTrue(Validator::make(['age' => '6'], ['age' => 'int|max:5'])->fails());
    }

    public function testNumericPassesAndFails(): void
    {
        self::assertTrue(Validator::make(['price' => '12.5'], ['price' => 'numeric'])->passes());
        self::assertTrue(Validator::make(['price' => 'abc'], ['price' => 'numeric'])->fails());
    }

    public function testIntPassesAndFails(): void
    {
        self::assertTrue(Validator::make(['qty' => '5'], ['qty' => 'int'])->passes());
        self::assertTrue(Validator::make(['qty' => '5.5'], ['qty' => 'int'])->fails());
    }

    public function testInPassesAndFails(): void
    {
        self::assertTrue(Validator::make(['status' => 'new'], ['status' => 'in:new,processing'])->passes());
        self::assertTrue(Validator::make(['status' => 'unknown'], ['status' => 'in:new,processing'])->fails());
    }

    public function testSamePassesAndFails(): void
    {
        $data = ['password' => 'secret1', 'password_confirmation' => 'secret1'];
        self::assertTrue(Validator::make($data, ['password_confirmation' => 'same:password'])->passes());

        $data2 = ['password' => 'secret1', 'password_confirmation' => 'different'];
        self::assertTrue(Validator::make($data2, ['password_confirmation' => 'same:password'])->fails());
    }

    public function testPhonePassesAndFails(): void
    {
        self::assertTrue(Validator::make(['phone' => '+7 (999) 123-45-67'], ['phone' => 'phone'])->passes());
        self::assertTrue(Validator::make(['phone' => '123'], ['phone' => 'phone'])->fails());
    }

    public function testUrlPassesAndFails(): void
    {
        self::assertTrue(Validator::make(['site' => 'https://example.com'], ['site' => 'url'])->passes());
        self::assertTrue(Validator::make(['site' => 'not a url'], ['site' => 'url'])->fails());
    }

    public function testBooleanPassesAndFails(): void
    {
        self::assertTrue(Validator::make(['flag' => '1'], ['flag' => 'boolean'])->passes());
        self::assertTrue(Validator::make(['flag' => 'maybe'], ['flag' => 'boolean'])->fails());
    }

    public function testDatePassesAndFails(): void
    {
        self::assertTrue(Validator::make(['d' => '2026-07-29'], ['d' => 'date'])->passes());
        self::assertTrue(Validator::make(['d' => 'not-a-date'], ['d' => 'date'])->fails());
    }

    public function testMultipleErrorsAccumulateForOneField(): void
    {
        $validator = Validator::make(['email' => 'x'], ['email' => 'required|email|min:5']);

        self::assertTrue($validator->fails());
        self::assertCount(2, $validator->errors()['email']);
    }

    public function testValidatedReturnsOnlyValidatedKeys(): void
    {
        $validator = Validator::make(
            ['name' => 'Anna', 'email' => 'anna@example.com', 'extra' => 'ignored'],
            ['name' => 'required', 'email' => 'required|email']
        );

        self::assertTrue($validator->passes());
        self::assertSame(['name' => 'Anna', 'email' => 'anna@example.com'], $validator->validated());
    }
}
