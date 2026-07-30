<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class ReviewRepository extends BaseRepository
{
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reviews (user_id, product_id, rating, body, status)
             VALUES (:user_id, :product_id, :rating, :body, :status)'
        );
        $stmt->execute([
            'user_id' => $data['user_id'],
            'product_id' => $data['product_id'] ?? null,
            'rating' => $data['rating'],
            'body' => $data['body'],
            'status' => $data['status'] ?? 'pending',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reviews WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function approved(int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM reviews WHERE status = 'approved' ORDER BY created_at DESC";

        if ($limit > 0) {
            $stmt = $this->pdo->prepare($sql . ' LIMIT :limit OFFSET :offset');
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->query($sql);
        }

        return $stmt->fetchAll();
    }

    public function featured(int $limit = 3): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM reviews WHERE status = 'approved' AND is_featured = 1 ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function forProduct(int $productId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM reviews WHERE product_id = ? AND status = 'approved' ORDER BY created_at DESC"
        );
        $stmt->execute([$productId]);

        return $stmt->fetchAll();
    }

    public function paginate(int $page, int $perPage, ?string $status = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        if ($status !== null && $status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }
        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM reviews {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT * FROM reviews {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    public function moderate(int $id, string $status, int $moderatorId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE reviews SET status = ?, moderated_by = ?, moderated_at = NOW() WHERE id = ?'
        );

        return $stmt->execute([$status, $moderatorId, $id]);
    }

    public function toggleFeatured(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE reviews SET is_featured = NOT is_featured WHERE id = ?');

        return $stmt->execute([$id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM reviews WHERE id = ?');

        return $stmt->execute([$id]);
    }

    public function countByStatus(): array
    {
        $stmt = $this->pdo->query('SELECT status, COUNT(*) AS cnt FROM reviews GROUP BY status');
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int) $row['cnt'];
        }

        return $result;
    }

    public function userReviewExists(int $userId, ?int $productId): bool
    {
        if ($productId === null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id IS NULL');
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = ?');
            $stmt->execute([$userId, $productId]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }

    public function averageForProduct(int $productId): ?float
    {
        $stmt = $this->pdo->prepare("SELECT AVG(rating) FROM reviews WHERE product_id = ? AND status = 'approved'");
        $stmt->execute([$productId]);
        $value = $stmt->fetchColumn();

        return $value !== null ? round((float) $value, 1) : null;
    }
}
