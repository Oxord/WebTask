<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Repository\ContactMessageRepository;
use Tests\Support\IntegrationTestCase;

final class ContactMessageRepositoryTest extends IntegrationTestCase
{
    public function testCreate(): void
    {
        $repo = new ContactMessageRepository(self::$pdo);

        $id = $repo->create([
            'name' => 'Клиент',
            'email' => 'client@example.test',
            'phone' => '+7 900 555-11-22',
            'message' => 'Здравствуйте, вопрос по доставке.',
        ]);

        self::assertGreaterThan(0, $id);
    }

    public function testUnreadCount(): void
    {
        $repo = new ContactMessageRepository(self::$pdo);
        $this->createContactMessage(['is_read' => 0]);
        $this->createContactMessage(['is_read' => 0]);
        $this->createContactMessage(['is_read' => 1]);

        self::assertSame(2, $repo->unreadCount());
    }

    public function testMarkRead(): void
    {
        $repo = new ContactMessageRepository(self::$pdo);
        $message = $this->createContactMessage(['is_read' => 0]);

        $repo->markRead((int) $message['id']);

        self::assertSame(0, $repo->unreadCount());
    }

    public function testPaginateUnreadOnly(): void
    {
        $repo = new ContactMessageRepository(self::$pdo);
        $this->createContactMessage(['is_read' => 0]);
        $this->createContactMessage(['is_read' => 1]);

        $unread = $repo->paginate(1, 10, true);
        self::assertSame(1, $unread['total']);

        $all = $repo->paginate(1, 10, null);
        self::assertSame(2, $all['total']);
    }

    public function testDelete(): void
    {
        $repo = new ContactMessageRepository(self::$pdo);
        $message = $this->createContactMessage();

        self::assertTrue($repo->delete((int) $message['id']));
        $all = $repo->paginate(1, 10, null);
        self::assertSame(0, $all['total']);
    }
}
