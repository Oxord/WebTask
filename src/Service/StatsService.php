<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use PDO;

class StatsService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function dashboard(int $days = 30): array
    {
        $days = max(1, $days);
        $since = date('Y-m-d 00:00:00', strtotime("-{$days} days"));

        return [
            'total_orders' => $this->totalOrders($since),
            'total_revenue' => $this->totalRevenue($since),
            'average_order_value' => $this->averageOrderValue($since),
            'orders_by_status' => $this->ordersByStatus($since),
            'revenue_by_day' => $this->revenueByDay($days),
            'top_products' => $this->topProducts($since),
            'new_customers' => $this->newCustomers($since),
            'pending_reviews' => $this->pendingReviews(),
            'total_products' => $this->totalProducts(),
            'unread_messages' => $this->unreadMessages(),
        ];
    }

    private function totalOrders(string $since): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM orders WHERE created_at >= ?');
        $stmt->execute([$since]);

        return (int) $stmt->fetchColumn();
    }

    private function totalRevenue(string $since): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE created_at >= ? AND status <> 'cancelled'"
        );
        $stmt->execute([$since]);

        return round((float) $stmt->fetchColumn(), 2);
    }

    private function averageOrderValue(string $since): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(AVG(total), 0) FROM orders WHERE created_at >= ? AND status <> 'cancelled'"
        );
        $stmt->execute([$since]);

        return round((float) $stmt->fetchColumn(), 2);
    }

    private function ordersByStatus(string $since): array
    {
        $stmt = $this->pdo->prepare('SELECT status, COUNT(*) AS cnt FROM orders WHERE created_at >= ? GROUP BY status');
        $stmt->execute([$since]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int) $row['cnt'];
        }

        return $result;
    }

    private function revenueByDay(int $days): array
    {
        $since = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));

        $stmt = $this->pdo->prepare(
            "SELECT DATE(created_at) AS d, COALESCE(SUM(total), 0) AS revenue, COUNT(*) AS orders
             FROM orders
             WHERE created_at >= ? AND status <> 'cancelled'
             GROUP BY DATE(created_at)"
        );
        $stmt->execute([$since]);

        $byDate = [];
        foreach ($stmt->fetchAll() as $row) {
            $byDate[$row['d']] = ['revenue' => round((float) $row['revenue'], 2), 'orders' => (int) $row['orders']];
        }

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = [
                'date' => $date,
                'revenue' => $byDate[$date]['revenue'] ?? 0.0,
                'orders' => $byDate[$date]['orders'] ?? 0,
            ];
        }

        return $series;
    }

    private function topProducts(string $since): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT oi.product_id, oi.product_name, SUM(oi.line_total) AS revenue, SUM(oi.qty) AS qty
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE o.created_at >= ? AND o.status <> 'cancelled'
             GROUP BY oi.product_id, oi.product_name
             ORDER BY revenue DESC
             LIMIT 5"
        );
        $stmt->execute([$since]);

        return $stmt->fetchAll();
    }

    private function newCustomers(string $since): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND created_at >= ?");
        $stmt->execute([$since]);

        return (int) $stmt->fetchColumn();
    }

    private function pendingReviews(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
    }

    private function totalProducts(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    private function unreadMessages(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
    }
}
