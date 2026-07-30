<?php
/** @var array $order */

use App\Core\View;
?>
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> /
    <a href="<?= e(url('/account')) ?>">Личный кабинет</a> /
    <a href="<?= e(url('/account/orders')) ?>">Мои заказы</a> /
    <span aria-current="page">№ <?= e($order['order_number']) ?></span>
</nav>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <h1>Заказ № <?= e($order['order_number']) ?></h1>
                <p class="text-muted">Оформлен <?= date('d.m.Y H:i', strtotime((string) $order['created_at'])) ?></p>
            </div>
            <?= View::partial('partials/order-status', ['status' => $order['status']]) ?>
        </div>

        <div class="checkout-layout">
            <div class="card">
                <h2 class="mt-0">Состав заказа</h2>
                <table class="cart-table">
                    <thead>
                        <tr><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Сумма</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td data-label="Товар"><?= e($item['product_name']) ?></td>
                                <td data-label="Цена"><?= price((float) $item['unit_price']) ?><?php if ((int) $item['discount_percent'] > 0): ?> <span class="badge badge--sale">−<?= (int) $item['discount_percent'] ?>%</span><?php endif; ?></td>
                                <td data-label="Кол-во"><?= (int) $item['qty'] ?></td>
                                <td data-label="Сумма"><?= price((float) $item['line_total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <aside class="cart-summary">
                <h2 class="mt-0">Доставка и оплата</h2>
                <p><strong>Получатель:</strong> <?= e($order['customer_name']) ?></p>
                <p><strong>Телефон:</strong> <?= e($order['customer_phone']) ?></p>
                <p><strong>Email:</strong> <?= e($order['customer_email']) ?></p>
                <p><strong>Адрес:</strong> <?= e($order['shipping_address']) ?></p>
                <?php if (!empty($order['comment'])): ?>
                    <p><strong>Комментарий:</strong> <?= e($order['comment']) ?></p>
                <?php endif; ?>
                <div class="cart-summary__row">
                    <span>Подытог</span>
                    <span><?= price((float) $order['subtotal']) ?></span>
                </div>
                <div class="cart-summary__row">
                    <span>Скидка</span>
                    <span>−<?= price((float) $order['discount_total']) ?></span>
                </div>
                <div class="cart-summary__row cart-summary__row--total">
                    <span>Итого</span>
                    <span><?= price((float) $order['total']) ?></span>
                </div>
            </aside>
        </div>
    </div>
</section>
