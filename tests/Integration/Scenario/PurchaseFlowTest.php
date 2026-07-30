<?php

declare(strict_types=1);

namespace Tests\Integration\Scenario;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Service\CartService;
use App\Service\OrderService;
use Tests\Support\IntegrationTestCase;

/**
 * End-to-end purchase cycle against the real database: add to cart -> create order from cart ->
 * verify persisted rows, stock decrement, cart clearing and order number format.
 */
final class PurchaseFlowTest extends IntegrationTestCase
{
    private function validCustomer(): array
    {
        return [
            'customer_name' => 'Мария Смирнова',
            'customer_email' => 'maria@example.test',
            'customer_phone' => '+7 999 222-33-44',
            'shipping_address' => 'г. Екатеринбург, ул. Малышева, д. 10',
            'comment' => 'Домофон не работает, звонить по телефону.',
        ];
    }

    private function makeServices(): array
    {
        $products = new ProductRepository(self::$pdo);
        $promotions = new PromotionRepository(self::$pdo);
        $orders = new OrderRepository(self::$pdo);
        $cart = new CartService($products, $promotions);
        $orderService = new OrderService($orders, $cart, $products);

        return [$cart, $orderService, $products, $orders, $promotions];
    }

    public function testFullPurchaseCycleCreatesOrderDecrementsStockAndClearsCart(): void
    {
        [$cart, $orderService, $products, $orders] = $this->makeServices();

        $user = $this->createUser();
        $product = $this->createProduct(['price' => '250.00', 'stock' => 5]);

        $cart->add((int) $product['id'], 2);
        self::assertSame(2, $cart->count());

        $orderId = $orderService->createFromCart($this->validCustomer(), (int) $user['id']);

        $withItems = $orders->findWithItems($orderId);
        self::assertNotNull($withItems);
        self::assertSame($user['id'], $withItems['user_id']);
        self::assertEqualsWithDelta(500.00, (float) $withItems['subtotal'], 0.001);
        self::assertEqualsWithDelta(500.00, (float) $withItems['total'], 0.001);
        self::assertEqualsWithDelta(0.00, (float) $withItems['discount_total'], 0.001);
        self::assertCount(1, $withItems['items']);
        self::assertSame(2, (int) $withItems['items'][0]['qty']);
        self::assertSame((int) $product['id'], (int) $withItems['items'][0]['product_id']);

        $updatedProduct = $products->find((int) $product['id']);
        self::assertSame(3, (int) $updatedProduct['stock']);

        self::assertTrue($cart->isEmpty());
    }

    public function testOrderNumberFormat(): void
    {
        [$cart, $orderService] = $this->makeServices();
        $product = $this->createProduct(['stock' => 5]);
        $cart->add((int) $product['id'], 1);

        $orderId = $orderService->createFromCart($this->validCustomer(), null);

        $orders = new OrderRepository(self::$pdo);
        $order = $orders->find($orderId);

        self::assertMatchesRegularExpression('/^DH-\d{8}-\d{4}$/', $order['order_number']);
        self::assertStringStartsWith('DH-' . date('Ymd') . '-', $order['order_number']);
    }

    public function testDiscountedProductAppliesDiscountToOrder(): void
    {
        [$cart, $orderService, , , $promotions] = $this->makeServices();

        $product = $this->createProduct(['price' => '200.00', 'stock' => 10]);
        $promo = $this->createPromotion(['discount_percent' => 30]);
        $promotions->attachProducts((int) $promo['id'], [$product['id']]);

        $cart->add((int) $product['id'], 2);
        $orderId = $orderService->createFromCart($this->validCustomer(), null);

        $orders = new OrderRepository(self::$pdo);
        $withItems = $orders->findWithItems($orderId);

        // 200 * 2 = 400 subtotal; 30% off -> unit final price 140, line total 280, discount 120.
        self::assertEqualsWithDelta(400.00, (float) $withItems['subtotal'], 0.001);
        self::assertEqualsWithDelta(120.00, (float) $withItems['discount_total'], 0.001);
        self::assertEqualsWithDelta(280.00, (float) $withItems['total'], 0.001);
        self::assertSame(30, (int) $withItems['items'][0]['discount_percent']);
        self::assertEqualsWithDelta(280.00, (float) $withItems['items'][0]['line_total'], 0.001);
    }

    public function testOrderIsGuestWhenNoUserIdProvided(): void
    {
        [$cart, $orderService] = $this->makeServices();
        $product = $this->createProduct(['stock' => 5]);
        $cart->add((int) $product['id'], 1);

        $orderId = $orderService->createFromCart($this->validCustomer(), null);

        $orders = new OrderRepository(self::$pdo);
        $order = $orders->find($orderId);

        self::assertNull($order['user_id']);
        self::assertSame('maria@example.test', $order['customer_email']);
    }
}
