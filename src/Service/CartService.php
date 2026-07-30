<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Session;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;

// Корзина хранится в $_SESSION['cart'] как [product_id => qty].
class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private readonly ProductRepository $products,
        private readonly PromotionRepository $promotions,
    ) {
    }

    public function add(int $productId, int $qty = 1): void
    {
        $product = $this->products->find($productId);
        if ($product === null || !(bool) $product['is_active']) {
            return;
        }

        $stock = (int) $product['stock'];
        if ($stock <= 0) {
            return;
        }

        $cart = $this->cart();
        $current = (int) ($cart[$productId] ?? 0);
        $desired = max(1, $current + max(1, $qty));
        $cart[$productId] = min($desired, $stock);
        $this->save($cart);
    }

    public function update(int $productId, int $qty): void
    {
        if ($qty <= 0) {
            $this->remove($productId);

            return;
        }

        $product = $this->products->find($productId);
        if ($product === null) {
            return;
        }

        $stock = (int) $product['stock'];
        if ($stock <= 0) {
            $this->remove($productId);

            return;
        }

        $cart = $this->cart();
        $cart[$productId] = min($qty, $stock);
        $this->save($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->cart();
        unset($cart[$productId]);
        $this->save($cart);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    public function isEmpty(): bool
    {
        return $this->cart() === [];
    }

    public function count(): int
    {
        return (int) array_sum($this->cart());
    }

    public function items(): array
    {
        $cart = $this->cart();
        if ($cart === []) {
            return [];
        }

        $products = $this->products->findMany(array_keys($cart));
        $discounts = $this->promotions->activeDiscountMap(array_keys($cart));

        $items = [];
        foreach ($products as $product) {
            $productId = (int) $product['id'];
            $qty = (int) $cart[$productId];
            $unitPrice = (float) $product['price'];
            $discountPercent = $discounts[$productId] ?? 0;
            $finalPrice = round($unitPrice * (1 - $discountPercent / 100), 2);

            $items[] = [
                'product' => $product,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'final_price' => $finalPrice,
                'line_total' => round($finalPrice * $qty, 2),
            ];
        }

        return $items;
    }

    public function totals(): array
    {
        $subtotal = 0.0;
        $total = 0.0;
        $count = 0;

        foreach ($this->items() as $item) {
            $subtotal = round($subtotal + $item['unit_price'] * $item['qty'], 2);
            $total = round($total + $item['line_total'], 2);
            $count += $item['qty'];
        }

        return [
            'subtotal' => $subtotal,
            'discount' => round($subtotal - $total, 2),
            'total' => $total,
            'count' => $count,
        ];
    }

    private function cart(): array
    {
        $cart = Session::get(self::SESSION_KEY, []);

        return is_array($cart) ? $cart : [];
    }

    private function save(array $cart): void
    {
        Session::set(self::SESSION_KEY, $cart);
    }
}
