<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Auth;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function makeAuth(): Auth
    {
        return new Auth($this->createMock(UserRepository::class));
    }

    public function testCheckIsFalseBeforeLoginAndTrueAfter(): void
    {
        $auth = $this->makeAuth();

        self::assertFalse($auth->check());

        $auth->login(['id' => 1, 'role' => 'user']);

        self::assertTrue($auth->check());
    }

    public function testLogoutClearsCheck(): void
    {
        $auth = $this->makeAuth();
        $auth->login(['id' => 1, 'role' => 'admin']);

        $auth->logout();

        self::assertFalse($auth->check());
        self::assertNull($auth->id());
    }

    public function testHasRole(): void
    {
        $auth = $this->makeAuth();
        $auth->login(['id' => 1, 'role' => 'moderator']);

        self::assertTrue($auth->hasRole('moderator'));
        self::assertTrue($auth->hasRole('admin', 'moderator'));
        self::assertFalse($auth->hasRole('admin'));
    }

    public static function permissionMatrix(): array
    {
        return [
            // permission, admin, moderator, user
            ['admin.access', true, true, false],
            ['products.manage', true, false, false],
            ['categories.manage', true, false, false],
            ['promotions.manage', true, false, false],
            ['users.manage', true, false, false],
            ['orders.manage', true, false, false],
            ['reviews.moderate', true, true, false],
            ['orders.view', true, true, false],
        ];
    }

    #[DataProvider('permissionMatrix')]
    public function testPermissionMatrixForAdmin(string $permission, bool $admin, bool $moderator, bool $user): void
    {
        $auth = $this->makeAuth();
        $auth->login(['id' => 1, 'role' => 'admin']);

        self::assertSame($admin, $auth->can($permission), "admin / {$permission}");
    }

    #[DataProvider('permissionMatrix')]
    public function testPermissionMatrixForModerator(string $permission, bool $admin, bool $moderator, bool $user): void
    {
        $auth = $this->makeAuth();
        $auth->login(['id' => 2, 'role' => 'moderator']);

        self::assertSame($moderator, $auth->can($permission), "moderator / {$permission}");
    }

    #[DataProvider('permissionMatrix')]
    public function testPermissionMatrixForUser(string $permission, bool $admin, bool $moderator, bool $user): void
    {
        $auth = $this->makeAuth();
        $auth->login(['id' => 3, 'role' => 'user']);

        self::assertSame($user, $auth->can($permission), "user / {$permission}");
    }

    #[DataProvider('permissionMatrix')]
    public function testPermissionMatrixForGuest(string $permission): void
    {
        $auth = $this->makeAuth();

        self::assertFalse($auth->can($permission), "guest / {$permission}");
    }

    public function testUnknownPermissionIsAlwaysFalse(): void
    {
        $auth = $this->makeAuth();
        $auth->login(['id' => 1, 'role' => 'admin']);

        self::assertFalse($auth->can('something.unknown'));
    }

    public function testIsAdminAndIsModerator(): void
    {
        $admin = $this->makeAuth();
        $admin->login(['id' => 1, 'role' => 'admin']);
        self::assertTrue($admin->isAdmin());
        self::assertFalse($admin->isModerator());

        $moderator = $this->makeAuth();
        $moderator->login(['id' => 2, 'role' => 'moderator']);
        self::assertTrue($moderator->isModerator());
        self::assertFalse($moderator->isAdmin());
    }
}
