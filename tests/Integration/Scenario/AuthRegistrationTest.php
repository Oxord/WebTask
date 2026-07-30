<?php

declare(strict_types=1);

namespace Tests\Integration\Scenario;

use App\Exception\ValidationException;
use App\Repository\UserRepository;
use App\Service\AuthService;
use Tests\Support\IntegrationTestCase;

final class AuthRegistrationTest extends IntegrationTestCase
{
    public function testRegisterHashesPasswordAndPersistsUser(): void
    {
        $service = new AuthService(new UserRepository(self::$pdo));

        $user = $service->register([
            'email' => 'newbie@example.test',
            'password' => 'plainpass1',
            'full_name' => 'Новый Пользователь',
            'phone' => '+7 999 111-22-33',
        ]);

        self::assertSame('newbie@example.test', $user['email']);
        self::assertNotSame('plainpass1', $user['password_hash']);
        self::assertTrue(password_verify('plainpass1', $user['password_hash']));
        self::assertSame('user', $user['role']);
        self::assertSame(1, (int) $user['is_active']);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $service = new AuthService(new UserRepository(self::$pdo));
        $service->register([
            'email' => 'dup@example.test',
            'password' => 'plainpass1',
            'full_name' => 'Первый',
        ]);

        $this->expectException(ValidationException::class);
        $service->register([
            'email' => 'dup@example.test',
            'password' => 'anotherpass',
            'full_name' => 'Второй',
        ]);
    }

    public function testAttemptSucceedsWithCorrectPasswordAndFailsWithWrongOne(): void
    {
        $service = new AuthService(new UserRepository(self::$pdo));
        $service->register([
            'email' => 'login@example.test',
            'password' => 'correcthorse',
            'full_name' => 'Логин Тестов',
        ]);

        $ok = $service->attempt('login@example.test', 'correcthorse');
        self::assertNotNull($ok);
        self::assertSame('login@example.test', $ok['email']);

        $bad = $service->attempt('login@example.test', 'wrongpassword');
        self::assertNull($bad);
    }

    public function testAttemptFailsForInactiveUser(): void
    {
        $users = new UserRepository(self::$pdo);
        $service = new AuthService($users);
        $user = $this->createUser([
            'email' => 'inactive@example.test',
            'password' => 'somepassword',
            'is_active' => 0,
        ]);

        $result = $service->attempt('inactive@example.test', 'somepassword');

        self::assertNull($result);
    }

    public function testAttemptFailsForUnknownEmail(): void
    {
        $service = new AuthService(new UserRepository(self::$pdo));

        self::assertNull($service->attempt('nobody-here@example.test', 'whatever'));
    }

    public function testChangePasswordRequiresCorrectCurrentPassword(): void
    {
        $users = new UserRepository(self::$pdo);
        $service = new AuthService($users);
        $user = $this->createUser(['password' => 'oldpassword']);

        $this->expectException(ValidationException::class);
        $service->changePassword((int) $user['id'], 'wrong-current', 'newpassword1');
    }

    public function testChangePasswordUpdatesHash(): void
    {
        $users = new UserRepository(self::$pdo);
        $service = new AuthService($users);
        $user = $this->createUser(['password' => 'oldpassword']);

        $service->changePassword((int) $user['id'], 'oldpassword', 'newpassword1');

        $updated = $users->find((int) $user['id']);
        self::assertTrue(password_verify('newpassword1', $updated['password_hash']));
    }
}
