<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use Throwable;

class PromotionRepository extends BaseRepository
{
    public function active(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM promotions WHERE is_active = 1 AND NOW() BETWEEN starts_at AND ends_at ORDER BY ends_at ASC'
        );

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM promotions WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM promotions WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM promotions ORDER BY created_at DESC');

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO promotions (title, slug, description, discount_percent, image_path, starts_at, ends_at, is_active)
             VALUES (:title, :slug, :description, :discount_percent, :image_path, :starts_at, :ends_at, :is_active)'
        );
        $stmt->execute($this->promotionParams($data));

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE promotions SET title = :title, slug = :slug, description = :description,
             discount_percent = :discount_percent, image_path = :image_path, starts_at = :starts_at,
             ends_at = :ends_at, is_active = :is_active WHERE id = :id'
        );
        $params = $this->promotionParams($data);
        $params['id'] = $id;

        return $stmt->execute($params);
    }

    private function promotionParams(array $data): array
    {
        return [
            'title' => $data['title'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'discount_percent' => $data['discount_percent'],
            'image_path' => $data['image_path'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => $data['is_active'] ?? 1,
        ];
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM promotions WHERE id = ?');

        return $stmt->execute([$id]);
    }

    public function attachProducts(int $promotionId, array $productIds): void
    {
        $this->pdo->beginTransaction();
        try {
            $del = $this->pdo->prepare('DELETE FROM promotion_products WHERE promotion_id = ?');
            $del->execute([$promotionId]);

            $insert = $this->pdo->prepare('INSERT INTO promotion_products (promotion_id, product_id) VALUES (?, ?)');
            foreach (array_unique(array_map('intval', $productIds)) as $productId) {
                $insert->execute([$promotionId, $productId]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function productIds(int $promotionId): array
    {
        $stmt = $this->pdo->prepare('SELECT product_id FROM promotion_products WHERE promotion_id = ?');
        $stmt->execute([$promotionId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function activeDiscountMap(array $productIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $productIds)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT pp.product_id, MAX(pr.discount_percent) AS max_discount
             FROM promotion_products pp
             JOIN promotions pr ON pr.id = pp.promotion_id
             WHERE pr.is_active = 1 AND NOW() BETWEEN pr.starts_at AND pr.ends_at
               AND pp.product_id IN ({$placeholders})
             GROUP BY pp.product_id"
        );
        $stmt->execute($ids);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['product_id']] = (int) $row['max_discount'];
        }

        return $map;
    }
}
