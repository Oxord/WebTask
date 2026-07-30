<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Service\CartService;
use PHPUnit\Framework\TestCase;

final class CartServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function product(int $id, string $price, int $stock, int $isActive = 1): array
    {
        return [
            'id' => $id,
            'name' => 'Товар ' . $id,
            'price' => $price,
            'stock' => $stock,
            'is_active' => $isActive,
        ];
    }

    private function makeService(array $product, array $discounts = []): CartService
    {
        $products = $this->createMock(ProductRepository::class);
        $products->method('find')->willReturn($product);
        $products->method('findMany')->willReturn([$product]);

        $promotions = $this->createMock(PromotionRepository::class);
        $promotions->method('activeDiscountMap')->willReturn($discounts);

        return new CartService($products, $promotions);
    }

    public function testAddCapsQuantityToStock(): void
    {
        $cart = $this->makeService($this->product(1, '100.00', 3));

        $cart->add(1, 10);

        self::assertSame(3, $cart->count());
    }

    public function testAddIgnoresInactiveProduct(): void
    {
        $cart = $this->makeService($this->product(1, '100.00', 5, 0));

        $cart->add(1, 1);

        self::assertTrue($cart->isEmpty());
    }

    public function testAddIgnoresOutOfStockProduct(): void
    {
        $cart = $this->makeService($this->product(1, '100.00', 0));

        $cart->add(1, 1);

        self::assertTrue($cart->isEmpty());
    }

    public function testUpdateSetsAbsoluteQuantityCappedByStock(): void
    {
        $cart = $this->makeService($this->product(1, '100.00', 5));

        $cart->update(1, 2);
        self::assertSame(2, $cart->count());

        $cart->update(1, 100);
        self::assertSame(5, $cart->count());
    }

    public function testUpdateWithZeroQuantityRemovesItem(): void
    {
        $cart = $this->makeService($this->product(1, '100.00', 5));

        $cart->add(1, 2);
        self::assertFalse($cart->isEmpty());

        $cart->update(1, 0);
        self::assertTrue($cart->isEmpty());
    }

    public function testRemove(): void
    {
        $cart = $this->makeService($this->product(1, '100.00', 5));

        $cart->add(1, 2);
        $cart->remove(1);

        self::assertTrue($cart->isEmpty());
        self::assertSame(0, $cart->count());
    }

    public function testClear(): void
    {
        $cart = $this->makeService($this->product(1, '100.00', 5));

        $cart->add(1, 2);
        $cart->clear();

        self::assertTrue($cart->isEmpty());
        self::assertSame(0, $cart->count());
    }

    public function testTotalsWithoutDiscount(): void
    {
        $cart = $this->makeService($this->product(1, '100.00', 5));

        $cart->add(1, 2);
        $totals = $cart->totals();

        self::assertEqualsWithDelta(200.0, $totals['subtotal'], 0.001);
        self::assertEqualsWithDelta(0.0, $totals['discount'], 0.001);
        self::assertEqualsWithDelta(200.0, $totals['total'], 0.001);
        self::assertSame(2, $totals['count']);
    }

    public function testTotalsAppliesPromotionDiscount(): void
    {
        $cart = $this->makeService($this->product(1, '100.00', 5), [1 => 20]);

        $cart->add(1, 2);
        $items = $cart->items();
        $totals = $cart->totals();

        self::assertEqualsWithDelta(80.0, $items[0]['final_price'], 0.001);
        self::assertEqualsWithDelta(160.0, $items[0]['line_total'], 0.001);
        self::assertEqualsWithDelta(200.0, $totals['subtotal'], 0.001);
        self::assertEqualsWithDelta(40.0, $totals['discount'], 0.001);
        self::assertEqualsWithDelta(160.0, $totals['total'], 0.001);
    }

    public function testMoneyIsRoundedToTwoDecimals(): void
    {
        $cart = $this->makeService($this->product(1, '33.33', 5), [1 => 15]);

        $cart->add(1, 3);
        $items = $cart->items();
        $totals = $cart->totals();

        // 33.33 * 0.85 = 28.3305 -> округляется до 28.33
        self::assertEqualsWithDelta(28.33, $items[0]['final_price'], 0.001);
        self::assertEqualsWithDelta(84.99, $items[0]['line_total'], 0.001);
        self::assertEqualsWithDelta(99.99, $totals['subtotal'], 0.001);
        self::assertEqualsWithDelta(15.0, $totals['discount'], 0.001);
        self::assertEqualsWithDelta(84.99, $totals['total'], 0.001);
    }
}
