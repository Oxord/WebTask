<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Repository\OrderRepository;
use RuntimeException;
use Tests\Support\IntegrationTestCase;

final class OrderRepositoryTest extends IntegrationTestCase
{
    private function orderPayload(array $overrides = []): array
    {
        return array_merge([
            'user_id' => null,
            'order_number' => 'DH-20260101-0001',
            'status' => 'new',
            'subtotal' => '300.00',
            'discount_total' => '50.00',
            'total' => '250.00',
            'customer_name' => 'Пётр Петров',
            'customer_email' => 'petr@example.test',
            'customer_phone' => '+7 999 000-11-22',
            'shipping_address' => 'г. Казань, ул. Баумана, д. 5',
            'comment' => 'Позвонить перед доставкой',
        ], $overrides);
    }

    private function itemPayload(array $overrides = []): array
    {
        $product = $this->createProduct(['price' => '150.00']);

        return array_merge([
            'product_id' => $product['id'],
            'product_name' => $product['name'],
            'unit_price' => '150.00',
            'discount_percent' => 0,
            'qty' => 2,
            'line_total' => '300.00',
        ], $overrides);
    }

    public function testCreateInsertsOrderAndItemsInsideItsOwnTransaction(): void
    {
        // Exercises the app's internal beginTransaction()/commit() inside our own outer test
        // transaction, relying on NestedTransactionPdo turning it into a SAVEPOINT.
        $repo = new OrderRepository(self::$pdo);
        $product = $this->createProduct(['price' => '150.00']);

        $orderId = $repo->create($this->orderPayload(), [
            [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'unit_price' => '150.00',
                'discount_percent' => 0,
                'qty' => 2,
                'line_total' => '300.00',
            ],
        ]);

        $withItems = $repo->findWithItems($orderId);
        self::assertNotNull($withItems);
        self::assertSame('DH-20260101-0001', $withItems['order_number']);
        self::assertEqualsWithDelta(250.00, (float) $withItems['total'], 0.001);
        self::assertCount(1, $withItems['items']);
        self::assertSame(2, (int) $withItems['items'][0]['qty']);
        self::assertEqualsWithDelta(300.00, (float) $withItems['items'][0]['line_total'], 0.001);
    }

    public function testFindWithItemsReturnsNullForMissingOrder(): void
    {
        $repo = new OrderRepository(self::$pdo);

        self::assertNull($repo->findWithItems(999999));
    }

    public function testForUserReturnsOrdersDescendingByCreatedAt(): void
    {
        $repo = new OrderRepository(self::$pdo);
        $user = $this->createUser();

        $first = $this->createOrder(['user_id' => $user['id'], 'order_number' => 'DH-20260101-0002']);
        self::$pdo->exec("UPDATE orders SET created_at = DATE_SUB(NOW(), INTERVAL 2 DAY) WHERE id = {$first['id']}");
        $second = $this->createOrder(['user_id' => $user['id'], 'order_number' => 'DH-20260101-0003']);
        $this->createOrder(['order_number' => 'DH-20260101-0004']); // другой пользователь — не должен попасть

        $orders = $repo->forUser((int) $user['id']);

        self::assertCount(2, $orders);
        self::assertSame($second['id'], $orders[0]['id']);
        self::assertSame($first['id'], $orders[1]['id']);
    }

    public function testUpdateStatus(): void
    {
        $repo = new OrderRepository(self::$pdo);
        $order = $this->createOrder(['status' => 'new']);

        $repo->updateStatus((int) $order['id'], 'shipped');

        $updated = $repo->find((int) $order['id']);
        self::assertSame('shipped', $updated['status']);
    }

    public function testUserHasCompletedOrder(): void
    {
        $repo = new OrderRepository(self::$pdo);
        $withCompleted = $this->createUser();
        $withoutCompleted = $this->createUser();

        $this->createOrder(['user_id' => $withCompleted['id'], 'status' => 'completed']);
        $this->createOrder(['user_id' => $withoutCompleted['id'], 'status' => 'new']);

        self::assertTrue($repo->userHasCompletedOrder((int) $withCompleted['id']));
        self::assertFalse($repo->userHasCompletedOrder((int) $withoutCompleted['id']));
    }

    public function testUserBoughtProductOnlyTrueWhenOrderIsCompleted(): void
    {
        $repo = new OrderRepository(self::$pdo);
        $user = $this->createUser();
        $product = $this->createProduct();

        $order = $this->createOrder(['user_id' => $user['id'], 'status' => 'new'], [
            ['product_id' => $product['id'], 'product_name' => $product['name'], 'unit_price' => '100.00', 'qty' => 1, 'line_total' => '100.00'],
        ]);

        self::assertFalse($repo->userBoughtProduct((int) $user['id'], (int) $product['id']));

        $repo->updateStatus((int) $order['id'], 'completed');

        self::assertTrue($repo->userBoughtProduct((int) $user['id'], (int) $product['id']));
    }

    public function testUserBoughtProductFalseForDifferentProduct(): void
    {
        $repo = new OrderRepository(self::$pdo);
        $user = $this->createUser();
        $bought = $this->createProduct();
        $notBought = $this->createProduct();

        $this->createOrder(['user_id' => $user['id'], 'status' => 'completed'], [
            ['product_id' => $bought['id'], 'product_name' => $bought['name'], 'unit_price' => '100.00', 'qty' => 1, 'line_total' => '100.00'],
        ]);

        self::assertTrue($repo->userBoughtProduct((int) $user['id'], (int) $bought['id']));
        self::assertFalse($repo->userBoughtProduct((int) $user['id'], (int) $notBought['id']));
    }

    public function testPaginateWithStatusFilter(): void
    {
        $repo = new OrderRepository(self::$pdo);
        $this->createOrder(['status' => 'new']);
        $this->createOrder(['status' => 'new']);
        $this->createOrder(['status' => 'cancelled']);

        $newOnly = $repo->paginate(1, 10, 'new');
        self::assertSame(2, $newOnly['total']);
        foreach ($newOnly['items'] as $item) {
            self::assertSame('new', $item['status']);
        }

        $all = $repo->paginate(1, 10, null);
        self::assertSame(3, $all['total']);
    }

    public function testPaginateComputesPagesCorrectly(): void
    {
        $repo = new OrderRepository(self::$pdo);
        for ($i = 0; $i < 5; $i++) {
            $this->createOrder(['status' => 'processing']);
        }

        $result = $repo->paginate(1, 2, 'processing');

        self::assertSame(5, $result['total']);
        self::assertSame(3, $result['pages']);
        self::assertCount(2, $result['items']);
    }

    public function testCreatePassesNewOrderIdToInsideTransactionCallback(): void
    {
        $repo = new OrderRepository(self::$pdo);
        $seen = null;

        $orderId = $repo->create(
            $this->orderPayload(['order_number' => 'DH-20260101-9100']),
            [$this->itemPayload()],
            function (int $id) use (&$seen): void {
                $seen = $id;
            }
        );

        self::assertSame($orderId, $seen);
    }

    public function testCreateRollsBackEverythingWhenCallbackThrows(): void
    {
        $repo = new OrderRepository(self::$pdo);
        $before = (int) self::$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

        try {
            $repo->create(
                $this->orderPayload(['order_number' => 'DH-20260101-9200']),
                [$this->itemPayload()],
                static function (): void {
                    throw new RuntimeException('остаток кончился');
                }
            );
            self::fail('Ожидалось исключение из колбэка.');
        } catch (RuntimeException $e) {
            self::assertSame('остаток кончился', $e->getMessage());
        }

        // Ни заказа, ни его позиций не должно остаться: списание остатков идёт в той же транзакции.
        self::assertSame($before, (int) self::$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn());
        self::assertNull(
            self::$pdo->query("SELECT id FROM orders WHERE order_number = 'DH-20260101-9200'")->fetchColumn() ?: null
        );
    }
}
