<?php

declare(strict_types=1);

namespace App\Repository;

class CategoryRepository extends BaseRepository
{
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC');

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (name, slug, description, sort_order) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['sort_order'] ?? 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE categories SET name = ?, slug = ?, description = ?, sort_order = ? WHERE id = ?'
        );

        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['sort_order'] ?? 0,
            $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = ?');

        return $stmt->execute([$id]);
    }

    public function withProductCounts(): array
    {
        $stmt = $this->pdo->query(
            'SELECT c.*, COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.name ASC'
        );

        return $stmt->fetchAll();
    }
}
