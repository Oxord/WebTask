<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exception\ValidationException;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\OrderService;
use PHPUnit\Framework\TestCase;

final class OrderServiceTest extends TestCase
{
    private function cartItem(int $productId, float $unitPrice, int $qty, int $discountPercent = 0, int $stock = 10): array
    {
        $finalPrice = round($unitPrice * (1 - $discountPercent / 100), 2);

        return [
            'product' => ['id' => $productId, 'name' => 'Ваза', 'stock' => $stock],
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'discount_percent' => $discountPercent,
            'final_price' => $finalPrice,
            'line_total' => round($finalPrice * $qty, 2),
        ];
    }

    private function validCustomer(): array
    {
        return [
            'customer_name' => 'Иван Иванов',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '+7 999 123-45-67',
            'shipping_address' => 'г. Москва, ул. Пушкина, д. 1',
            'comment' => '',
        ];
    }

    public function testThrowsValidationExceptionOnEmptyCart(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $cart = $this->createMock(CartService::class);
        $cart->method('isEmpty')->willReturn(true);
        $products = $this->createMock(ProductRepository::class);

        $service = new OrderService($orders, $cart, $products);

        $this->expectException(ValidationException::class);
        $service->createFromCart($this->validCustomer(), null);
    }

    public function testThrowsValidationExceptionOnInvalidCustomerData(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $cart = $this->createMock(CartService::class);
        $cart->method('isEmpty')->willReturn(false);
        $cart->method('items')->willReturn([$this->cartItem(1, 100.0, 1)]);
        $products = $this->createMock(ProductRepository::class);

        $service = new OrderService($orders, $cart, $products);

        $this->expectException(ValidationException::class);
        $service->createFromCart([
            'customer_name' => '',
            'customer_email' => 'not-an-email',
            'customer_phone' => '123',
            'shipping_address' => 'a',
        ], null);
    }

    public function testOrderNumberFormat(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $captured = null;
        $orders->method('create')->willReturnCallback(function (array $order, array $items) use (&$captured): int {
            $captured = $order;

            return 501;
        });

        $cart = $this->createMock(CartService::class);
        $cart->method('isEmpty')->willReturn(false);
        $cart->method('items')->willReturn([$this->cartItem(1, 100.0, 2)]);
        $cart->expects(self::once())->method('clear');

        $products = $this->createMock(ProductRepository::class);
        $products->method('decrementStock')->willReturn(true);

        $service = new OrderService($orders, $cart, $products);
        $orderId = $service->createFromCart($this->validCustomer(), null);

        self::assertSame(501, $orderId);
        self::assertMatchesRegularExpression('/^DH-\d{8}-\d{4}$/', $captured['order_number']);
    }

    public function testCalculatesSubtotalDiscountTotalCorrectly(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $captured = null;
        $orders->method('create')->willReturnCallback(function (array $order, array $items) use (&$captured): int {
            $captured = $order;

            return 1;
        });

        $cart = $this->createMock(CartService::class);
        $cart->method('isEmpty')->willReturn(false);
        // 100.00 за штуку, скидка 20%, 2 шт: subtotal 200, total 160, discount 40
        $cart->method('items')->willReturn([$this->cartItem(1, 100.0, 2, 20)]);

        $products = $this->createMock(ProductRepository::class);
        $products->method('decrementStock')->willReturn(true);

        $service = new OrderService($orders, $cart, $products);
        $service->createFromCart($this->validCustomer(), 7);

        self::assertEqualsWithDelta(200.0, $captured['subtotal'], 0.001);
        self::assertEqualsWithDelta(40.0, $captured['discount_total'], 0.001);
        self::assertEqualsWithDelta(160.0, $captured['total'], 0.001);
        self::assertSame(7, $captured['user_id']);
    }

    public function testAllowedStatusesWhitelist(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $cart = $this->createMock(CartService::class);
        $products = $this->createMock(ProductRepository::class);

        $service = new OrderService($orders, $cart, $products);

        self::assertSame(['new', 'processing', 'shipped', 'completed', 'cancelled'], $service->allowedStatuses());
    }

    public function testUpdateStatusRejectsUnknownStatus(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $orders->expects(self::never())->method('updateStatus');
        $cart = $this->createMock(CartService::class);
        $products = $this->createMock(ProductRepository::class);

        $service = new OrderService($orders, $cart, $products);

        $this->expectException(ValidationException::class);
        $service->updateStatus(1, 'bogus-status');
    }

    public function testUpdateStatusAcceptsKnownStatus(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $orders->expects(self::once())->method('updateStatus')->with(1, 'shipped')->willReturn(true);
        $cart = $this->createMock(CartService::class);
        $products = $this->createMock(ProductRepository::class);

        $service = new OrderService($orders, $cart, $products);
        $service->updateStatus(1, 'shipped');
    }

    public function testStockIsDecrementedInsideOrderTransaction(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $callbackRan = false;
        $orders->method('create')->willReturnCallback(
            function (array $order, array $items, ?callable $insideTransaction = null) use (&$callbackRan): int {
                // Списание остатков обязано выполняться внутри транзакции заказа, а не после неё.
                self::assertNotNull($insideTransaction);
                $insideTransaction(777);
                $callbackRan = true;

                return 777;
            }
        );

        $cart = $this->createMock(CartService::class);
        $cart->method('isEmpty')->willReturn(false);
        $cart->method('items')->willReturn([$this->cartItem(1, 100.0, 2)]);

        $products = $this->createMock(ProductRepository::class);
        $products->expects(self::once())->method('decrementStock')->with(1, 2)->willReturn(true);

        $service = new OrderService($orders, $cart, $products);

        self::assertSame(777, $service->createFromCart($this->validCustomer(), null));
        self::assertTrue($callbackRan);
    }

    public function testOrderFailsWhenStockRanOutBeforeDecrement(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('create')->willReturnCallback(
            static function (array $order, array $items, ?callable $insideTransaction = null): int {
                $insideTransaction(1);

                return 1;
            }
        );

        $cart = $this->createMock(CartService::class);
        $cart->method('isEmpty')->willReturn(false);
        $cart->method('items')->willReturn([$this->cartItem(1, 100.0, 2)]);
        // Корзину нельзя очищать: заказ не состоялся.
        $cart->expects(self::never())->method('clear');

        $products = $this->createMock(ProductRepository::class);
        $products->method('decrementStock')->willReturn(false);

        $service = new OrderService($orders, $cart, $products);

        $this->expectException(ValidationException::class);
        $service->createFromCart($this->validCustomer(), null);
    }
}
