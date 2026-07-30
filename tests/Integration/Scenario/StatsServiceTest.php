<?php

declare(strict_types=1);

namespace Tests\Integration\Scenario;

use App\Service\StatsService;
use Tests\Support\IntegrationTestCase;

final class StatsServiceTest extends IntegrationTestCase
{
    public function testDashboardExcludesCancelledFromRevenueAndAverage(): void
    {
        $service = new StatsService(self::$pdo);
        $this->createOrder(['status' => 'completed', 'total' => '1000.00']);
        $this->createOrder(['status' => 'completed', 'total' => '500.00']);
        $this->createOrder(['status' => 'cancelled', 'total' => '9999.00']);

        $result = $service->dashboard(7);

        // total_orders counts everything regardless of status.
        self::assertSame(3, $result['total_orders']);
        // revenue / average must exclude the cancelled order entirely.
        self::assertEqualsWithDelta(1500.00, $result['total_revenue'], 0.001);
        self::assertEqualsWithDelta(750.00, $result['average_order_value'], 0.001);
    }

    public function testOrdersByStatusCountsEachStatusSeparately(): void
    {
        $service = new StatsService(self::$pdo);
        $this->createOrder(['status' => 'new']);
        $this->createOrder(['status' => 'new']);
        $this->createOrder(['status' => 'completed']);

        $result = $service->dashboard(7);

        self::assertSame(2, $result['orders_by_status']['new']);
        self::assertSame(1, $result['orders_by_status']['completed']);
    }

    public function testRevenueByDayIncludesAllDaysInRangeEvenWithZeroOrders(): void
    {
        $service = new StatsService(self::$pdo);
        $this->createOrder(['status' => 'completed', 'total' => '200.00']);

        $result = $service->dashboard(5);
        $series = $result['revenue_by_day'];

        self::assertCount(5, $series);

        $today = date('Y-m-d');
        $todayEntry = null;
        $zeroDays = 0;
        foreach ($series as $entry) {
            if ($entry['date'] === $today) {
                $todayEntry = $entry;
            } else {
                self::assertEqualsWithDelta(0.0, $entry['revenue'], 0.001);
                self::assertSame(0, $entry['orders']);
                $zeroDays++;
            }
        }

        self::assertNotNull($todayEntry);
        self::assertEqualsWithDelta(200.00, $todayEntry['revenue'], 0.001);
        self::assertSame(1, $todayEntry['orders']);
        self::assertSame(4, $zeroDays);
    }

    public function testRevenueByDayExcludesCancelledOrders(): void
    {
        $service = new StatsService(self::$pdo);
        $this->createOrder(['status' => 'cancelled', 'total' => '5000.00']);

        $result = $service->dashboard(3);

        foreach ($result['revenue_by_day'] as $entry) {
            self::assertEqualsWithDelta(0.0, $entry['revenue'], 0.001);
        }
    }

    public function testTopProductsSortedByRevenueDescendingAndExcludesCancelled(): void
    {
        $service = new StatsService(self::$pdo);
        $this->createOrder(['status' => 'completed'], [
            ['product_id' => null, 'product_name' => 'Товар А', 'unit_price' => '100.00', 'qty' => 1, 'line_total' => '100.00'],
        ]);
        $this->createOrder(['status' => 'completed'], [
            ['product_id' => null, 'product_name' => 'Товар Б', 'unit_price' => '500.00', 'qty' => 1, 'line_total' => '500.00'],
        ]);
        $this->createOrder(['status' => 'cancelled'], [
            ['product_id' => null, 'product_name' => 'Товар В', 'unit_price' => '900.00', 'qty' => 1, 'line_total' => '900.00'],
        ]);

        $result = $service->dashboard(7);
        $top = $result['top_products'];

        self::assertSame('Товар Б', $top[0]['product_name']);
        self::assertEqualsWithDelta(500.00, (float) $top[0]['revenue'], 0.001);
        self::assertSame('Товар А', $top[1]['product_name']);
        self::assertNotContains('Товар В', array_column($top, 'product_name'));
    }

    public function testDashboardCountsPendingReviewsNewCustomersAndUnreadMessages(): void
    {
        $service = new StatsService(self::$pdo);
        // Pin reviews to a single reviewer/product so createReview()'s own createUser()/
        // createProduct() fallbacks don't add extra rows and skew the counts below.
        $reviewer = $this->createUser(['role' => 'user']);
        $product1 = $this->createProduct();
        $product2 = $this->createProduct();
        $this->createReview(['status' => 'pending', 'user_id' => $reviewer['id'], 'product_id' => $product1['id']]);
        $this->createReview(['status' => 'approved', 'user_id' => $reviewer['id'], 'product_id' => $product2['id']]);
        $this->createUser(['role' => 'admin']); // не должен считаться как "new customer"
        $this->createContactMessage(['is_read' => 0]);
        $this->createContactMessage(['is_read' => 1]);

        $result = $service->dashboard(7);

        self::assertSame(1, $result['pending_reviews']);
        self::assertSame(1, $result['new_customers']);
        self::assertSame(1, $result['unread_messages']);
        self::assertSame(2, $result['total_products']);
    }

    public function testDashboardOnlyCountsOrdersWithinWindow(): void
    {
        $service = new StatsService(self::$pdo);
        $recent = $this->createOrder(['status' => 'completed', 'total' => '100.00']);
        $old = $this->createOrder(['status' => 'completed', 'total' => '900.00']);
        self::$pdo->exec("UPDATE orders SET created_at = DATE_SUB(NOW(), INTERVAL 60 DAY) WHERE id = {$old['id']}");

        $result = $service->dashboard(30);

        self::assertSame(1, $result['total_orders']);
        self::assertEqualsWithDelta(100.00, $result['total_revenue'], 0.001);
    }
}
