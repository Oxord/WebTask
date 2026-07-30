<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Repository\UserRepository;
use Tests\Support\IntegrationTestCase;

final class UserRepositoryTest extends IntegrationTestCase
{
    public function testCreateAndFind(): void
    {
        $repo = new UserRepository(self::$pdo);

        $id = $repo->create([
            'email' => 'alice@example.test',
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
            'full_name' => 'Алиса Тестова',
            'phone' => '+7 900 111-22-33',
            'address' => 'г. Москва, ул. Ленина, д. 1',
        ]);

        $user = $repo->find($id);

        self::assertNotNull($user);
        self::assertSame('alice@example.test', $user['email']);
        self::assertSame('Алиса Тестова', $user['full_name']);
        self::assertSame('user', $user['role']);
        self::assertSame(1, (int) $user['is_active']);
    }

    public function testFindReturnsNullForMissingId(): void
    {
        $repo = new UserRepository(self::$pdo);

        self::assertNull($repo->find(999999));
    }

    public function testFindByEmail(): void
    {
        $repo = new UserRepository(self::$pdo);
        $created = $this->createUser(['email' => 'bob@example.test']);

        $found = $repo->findByEmail('bob@example.test');

        self::assertNotNull($found);
        self::assertSame($created['id'], $found['id']);
    }

    public function testEmailExistsExcludingOwnId(): void
    {
        $repo = new UserRepository(self::$pdo);
        $user = $this->createUser(['email' => 'carl@example.test']);

        self::assertTrue($repo->emailExists('carl@example.test'));
        self::assertFalse($repo->emailExists('carl@example.test', (int) $user['id']));
        self::assertFalse($repo->emailExists('nobody@example.test'));
    }

    public function testUpdate(): void
    {
        $repo = new UserRepository(self::$pdo);
        $user = $this->createUser();

        $ok = $repo->update((int) $user['id'], [
            'full_name' => 'Новое Имя',
            'phone' => '+7 900 999-88-77',
            'address' => 'г. Санкт-Петербург',
            'email' => 'updated@example.test',
        ]);

        self::assertTrue($ok);
        $updated = $repo->find((int) $user['id']);
        self::assertSame('Новое Имя', $updated['full_name']);
        self::assertSame('+7 900 999-88-77', $updated['phone']);
        self::assertSame('updated@example.test', $updated['email']);
    }

    public function testUpdateWithNoRecognisedFieldsReturnsFalse(): void
    {
        $repo = new UserRepository(self::$pdo);
        $user = $this->createUser();

        self::assertFalse($repo->update((int) $user['id'], ['role' => 'admin']));
    }

    public function testUpdatePassword(): void
    {
        $repo = new UserRepository(self::$pdo);
        $user = $this->createUser();
        $newHash = password_hash('brandnew', PASSWORD_BCRYPT);

        $repo->updatePassword((int) $user['id'], $newHash);

        $updated = $repo->find((int) $user['id']);
        self::assertSame($newHash, $updated['password_hash']);
    }

    public function testSetRole(): void
    {
        $repo = new UserRepository(self::$pdo);
        $user = $this->createUser(['role' => 'user']);

        $repo->setRole((int) $user['id'], 'moderator');

        $updated = $repo->find((int) $user['id']);
        self::assertSame('moderator', $updated['role']);
    }

    public function testDelete(): void
    {
        $repo = new UserRepository(self::$pdo);
        $user = $this->createUser();

        self::assertTrue($repo->delete((int) $user['id']));
        self::assertNull($repo->find((int) $user['id']));
    }

    public function testPaginateFiltersByRoleOnly(): void
    {
        $repo = new UserRepository(self::$pdo);
        $this->createUser(['full_name' => 'Zebra Search Match', 'email' => 'zsearch1@example.test', 'role' => 'admin']);
        $this->createUser(['full_name' => 'Other Person', 'email' => 'zsearch2@example.test', 'role' => 'user']);
        $this->createUser(['full_name' => 'Third Zebra', 'email' => 'other3@example.test', 'role' => 'user']);

        $byRole = $repo->paginate(1, 10, null, 'admin');
        self::assertGreaterThanOrEqual(1, $byRole['total']);
        foreach ($byRole['items'] as $item) {
            self::assertSame('admin', $item['role']);
        }
    }

    public function testPaginateFiltersBySearchAndRole(): void
    {
        $repo = new UserRepository(self::$pdo);
        $this->createUser(['full_name' => 'Zebra Search Match', 'email' => 'zsearch1@example.test', 'role' => 'admin']);
        $this->createUser(['full_name' => 'Other Person', 'email' => 'zsearch2@example.test', 'role' => 'user']);
        $this->createUser(['full_name' => 'Third Zebra', 'email' => 'other3@example.test', 'role' => 'user']);

        $byName = $repo->paginate(1, 10, 'Zebra', null);
        self::assertSame(2, $byName['total']);

        $byBoth = $repo->paginate(1, 10, 'Zebra', 'admin');
        self::assertSame(1, $byBoth['total']);
        self::assertSame('Zebra Search Match', $byBoth['items'][0]['full_name']);
    }

    public function testPaginateRespectsPageSizeAndComputesPages(): void
    {
        $repo = new UserRepository(self::$pdo);
        for ($i = 0; $i < 5; $i++) {
            $this->createUser(['full_name' => "PaginationUser {$i}"]);
        }

        $result = $repo->paginate(1, 2, 'PaginationUser', null);

        self::assertSame(5, $result['total']);
        self::assertSame(3, $result['pages']);
        self::assertCount(2, $result['items']);

        $lastPage = $repo->paginate(3, 2, 'PaginationUser', null);
        self::assertCount(1, $lastPage['items']);
    }

    public function testCountByRole(): void
    {
        $repo = new UserRepository(self::$pdo);
        $this->createUser(['role' => 'admin']);
        $this->createUser(['role' => 'admin']);
        $this->createUser(['role' => 'moderator']);
        $this->createUser(['role' => 'user']);

        $counts = $repo->countByRole();

        self::assertSame(2, $counts['admin']);
        self::assertSame(1, $counts['moderator']);
        self::assertSame(1, $counts['user']);
    }
}
