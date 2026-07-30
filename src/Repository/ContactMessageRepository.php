<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class ContactMessageRepository extends BaseRepository
{
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['message'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function paginate(int $page, int $perPage, ?bool $unreadOnly = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $whereSql = $unreadOnly === true ? 'WHERE is_read = 0' : '';

        $total = (int) $this->pdo->query("SELECT COUNT(*) FROM contact_messages {$whereSql}")->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT * FROM contact_messages {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    public function markRead(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?');

        return $stmt->execute([$id]);
    }

    public function unreadCount(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM contact_messages WHERE id = ?');

        return $stmt->execute([$id]);
    }
}
