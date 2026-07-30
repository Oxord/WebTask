<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class ProductRepository extends BaseRepository
{
    // Единый LEFT JOIN на максимальную активную скидку — переиспользуется во всех выборках.
    private const DISCOUNT_JOIN = '
        LEFT JOIN (
            SELECT pp.product_id, MAX(pr.discount_percent) AS max_discount
            FROM promotion_products pp
            JOIN promotions pr ON pr.id = pp.promotion_id
            WHERE pr.is_active = 1 AND NOW() BETWEEN pr.starts_at AND pr.ends_at
            GROUP BY pp.product_id
        ) d ON d.product_id = p.id
    ';

    private const DISCOUNT_SELECT = '
        COALESCE(d.max_discount, 0) AS discount_percent,
        ROUND(p.price * (1 - COALESCE(d.max_discount, 0) / 100), 2) AS final_price
    ';

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, ' . self::DISCOUNT_SELECT . ' FROM products p ' . self::DISCOUNT_JOIN . ' WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Скрытые (is_active=0) товары не должны открываться по прямой ссылке в каталоге.
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, ' . self::DISCOUNT_SELECT . ' FROM products p ' . self::DISCOUNT_JOIN . '
             WHERE p.slug = ? AND p.is_active = 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Не фильтрует is_active — нужен там, где важен факт занятости slug вне зависимости
    // от активности товара (например, проверка уникальности slug в админке).
    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        if ($exceptId !== null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ? AND id <> ?');
            $stmt->execute([$slug, $exceptId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ?');
            $stmt->execute([$slug]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }

    public function featured(int $limit = 6): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, ' . self::DISCOUNT_SELECT . ' FROM products p ' . self::DISCOUNT_JOIN . '
             WHERE p.is_featured = 1 AND p.is_active = 1
             ORDER BY p.created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function latest(int $limit): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, ' . self::DISCOUNT_SELECT . ' FROM products p ' . self::DISCOUNT_JOIN . '
             WHERE p.is_active = 1
             ORDER BY p.created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            'SELECT p.*, ' . self::DISCOUNT_SELECT . ' FROM products p ' . self::DISCOUNT_JOIN . "
             WHERE p.id IN ({$placeholders})"
        );
        $stmt->execute($ids);

        $byId = [];
        foreach ($stmt->fetchAll() as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    public function search(array $filters): array
    {
        $where = ['p.is_active = 1'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            if (mb_strlen($q) >= 3) {
                $where[] = 'MATCH(p.name, p.description) AGAINST (:q IN NATURAL LANGUAGE MODE)';
                $params['q'] = $q;
            } else {
                // Нативные prepared statements не допускают повторного использования одного плейсхолдера.
                $where[] = '(p.name LIKE :qlike_name OR p.description LIKE :qlike_desc)';
                $params['qlike_name'] = '%' . $q . '%';
                $params['qlike_desc'] = '%' . $q . '%';
            }
        }

        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (isset($filters['price_min']) && $filters['price_min'] !== '') {
            $where[] = 'p.price >= :price_min';
            $params['price_min'] = (float) $filters['price_min'];
        }

        if (isset($filters['price_max']) && $filters['price_max'] !== '') {
            $where[] = 'p.price <= :price_max';
            $params['price_max'] = (float) $filters['price_max'];
        }

        if (!empty($filters['in_stock'])) {
            $where[] = 'p.stock > 0';
        }

        if (!empty($filters['on_sale'])) {
            $where[] = 'd.max_discount IS NOT NULL';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sortMap = [
            'price_asc' => 'final_price ASC',
            'price_desc' => 'final_price DESC',
            'newest' => 'p.created_at DESC',
            'name' => 'p.name ASC',
        ];
        $sort = $sortMap[$filters['sort'] ?? ''] ?? 'p.created_at DESC';

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, (int) ($filters['per_page'] ?? 12));
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM products p ' . self::DISCOUNT_JOIN . " {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            'SELECT p.*, ' . self::DISCOUNT_SELECT . '
             FROM products p ' . self::DISCOUNT_JOIN . "
             {$whereSql}
             ORDER BY {$sort}
             LIMIT :limit OFFSET :offset"
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
            'page' => $page,
        ];
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (category_id, name, slug, description, price, image_path, stock, is_featured, is_active)
             VALUES (:category_id, :name, :slug, :description, :price, :image_path, :stock, :is_featured, :is_active)'
        );
        $stmt->execute($this->productParams($data));

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE products SET category_id = :category_id, name = :name, slug = :slug, description = :description,
             price = :price, image_path = :image_path, stock = :stock, is_featured = :is_featured, is_active = :is_active
             WHERE id = :id'
        );
        $params = $this->productParams($data);
        $params['id'] = $id;

        return $stmt->execute($params);
    }

    private function productParams(array $data): array
    {
        return [
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'image_path' => $data['image_path'] ?? null,
            'stock' => $data['stock'] ?? 0,
            'is_featured' => $data['is_featured'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
        ];
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = ?');

        return $stmt->execute([$id]);
    }

    // Атомарный декремент остатка — используется OrderService при оформлении заказа.
    /**
     * Условие `stock >= ?` — защита от одновременной покупки последних единиц: два
     * параллельных заказа не смогут списать один и тот же остаток. false означает,
     * что товара уже не хватает, и заказ нужно отклонить.
     */
    public function decrementStock(int $id, int $qty): bool
    {
        $stmt = $this->pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
        $stmt->execute([$qty, $id, $qty]);

        return $stmt->rowCount() > 0;
    }

    public function paginateAdmin(int $page, int $perPage, ?string $search = null, ?int $categoryId = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        if ($search !== null && $search !== '') {
            // Нативные prepared statements не допускают повторного использования одного плейсхолдера.
            $where[] = '(p.name LIKE :search_name OR p.slug LIKE :search_slug)';
            $params['search_name'] = '%' . $search . '%';
            $params['search_slug'] = '%' . $search . '%';
        }
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM products p {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            'SELECT p.*, ' . self::DISCOUNT_SELECT . '
             FROM products p ' . self::DISCOUNT_JOIN . "
             {$whereSql}
             ORDER BY p.created_at DESC
             LIMIT :limit OFFSET :offset"
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

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }
}
