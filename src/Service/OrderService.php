<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Validator;
use App\Exception\ValidationException;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;

class OrderService
{
    private const STATUSES = ['new', 'processing', 'shipped', 'completed', 'cancelled'];

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly CartService $cart,
        private readonly ProductRepository $products,
    ) {
    }

    public function createFromCart(array $customer, ?int $userId): int
    {
        if ($this->cart->isEmpty()) {
            throw new ValidationException(['cart' => ['Корзина пуста.']]);
        }

        $validator = Validator::make($customer, [
            'customer_name' => 'required|min:2|max:150',
            'customer_email' => 'required|email|max:190',
            'customer_phone' => 'required|phone',
            'shipping_address' => 'required|min:5|max:255',
            'comment' => 'max:2000',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
        $data = $validator->validated();

        $subtotal = 0.0;
        $total = 0.0;
        $orderItems = [];

        foreach ($this->cart->items() as $item) {
            $product = $item['product'];
            $qty = min((int) $item['qty'], (int) $product['stock']);
            if ($qty <= 0) {
                continue;
            }

            $lineTotal = round($item['final_price'] * $qty, 2);
            $subtotal = round($subtotal + $item['unit_price'] * $qty, 2);
            $total = round($total + $lineTotal, 2);

            $orderItems[] = [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'unit_price' => $item['unit_price'],
                'discount_percent' => $item['discount_percent'],
                'qty' => $qty,
                'line_total' => $lineTotal,
            ];
        }

        if ($orderItems === []) {
            throw new ValidationException(['cart' => ['Товары в корзине недоступны для заказа.']]);
        }

        $order = [
            'user_id' => $userId,
            'order_number' => $this->generateOrderNumber(),
            'status' => 'new',
            'subtotal' => $subtotal,
            'discount_total' => round($subtotal - $total, 2),
            'total' => $total,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'shipping_address' => $data['shipping_address'],
            'comment' => $data['comment'] ?? null,
        ];

        // Остатки списываем внутри транзакции заказа: между показом корзины и оформлением
        // товар мог разобрать кто-то ещё, и тогда весь заказ должен откатиться, а не создаться
        // с недостающим товаром.
        $orderId = $this->orders->create($order, $orderItems, function () use ($orderItems): void {
            foreach ($orderItems as $item) {
                if (!$this->products->decrementStock((int) $item['product_id'], (int) $item['qty'])) {
                    throw new ValidationException([
                        'cart' => [sprintf('Товар «%s» только что закончился. Обновите корзину.', $item['product_name'])],
                    ]);
                }
            }
        });

        $this->cart->clear();

        return $orderId;
    }

    public function allowedStatuses(): array
    {
        return self::STATUSES;
    }

    public function updateStatus(int $orderId, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new ValidationException(['status' => ['Недопустимый статус заказа.']]);
        }

        $this->orders->updateStatus($orderId, $status);
    }

    private function generateOrderNumber(): string
    {
        return sprintf('DH-%s-%04d', date('Ymd'), random_int(0, 9999));
    }
}
