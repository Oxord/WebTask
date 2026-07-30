<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use Throwable;

class OrderRepository extends BaseRepository
{
    /**
     * @param callable(int):void|null $insideTransaction Выполняется до commit — сюда попадает
     *        списание остатков, чтобы заказ и остатки менялись атомарно. Исключение из колбэка
     *        откатывает весь заказ.
     */
    public function create(array $order, array $items, ?callable $insideTransaction = null): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO orders (user_id, order_number, status, subtotal, discount_total, total,
                 customer_name, customer_email, customer_phone, shipping_address, comment)
                 VALUES (:user_id, :order_number, :status, :subtotal, :discount_total, :total,
                 :customer_name, :customer_email, :customer_phone, :shipping_address, :comment)'
            );
            $stmt->execute([
                'user_id' => $order['user_id'] ?? null,
                'order_number' => $order['order_number'],
                'status' => $order['status'] ?? 'new',
                'subtotal' => $order['subtotal'],
                'discount_total' => $order['discount_total'] ?? 0,
                'total' => $order['total'],
                'customer_name' => $order['customer_name'],
                'customer_email' => $order['customer_email'],
                'customer_phone' => $order['customer_phone'],
                'shipping_address' => $order['shipping_address'],
                'comment' => $order['comment'] ?? null,
            ]);
            $orderId = (int) $this->pdo->lastInsertId();

            $itemStmt = $this->pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, unit_price, discount_percent, qty, line_total)
                 VALUES (:order_id, :product_id, :product_name, :unit_price, :discount_percent, :qty, :line_total)'
            );
            foreach ($items as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'qty' => $item['qty'],
                    'line_total' => $item['line_total'],
                ]);
            }

            if ($insideTransaction !== null) {
                $insideTransaction($orderId);
            }

            $this->pdo->commit();

            return $orderId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findWithItems(int $id): ?array
    {
        $order = $this->find($id);
        if ($order === null) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $stmt->execute([$id]);
        $order['items'] = $stmt->fetchAll();

        return $order;
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);

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

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT * FROM orders {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
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

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');

        return $stmt->execute([$status, $id]);
    }

    public function userHasCompletedOrder(int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'completed'");
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function userBoughtProduct(int $userId, int $productId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = ? AND o.status = 'completed' AND oi.product_id = ?"
        );
        $stmt->execute([$userId, $productId]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
