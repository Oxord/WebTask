<?php
/**
 * @var array $order
 * @var \App\Core\Auth $auth
 */
?>
<section class="section">
    <div class="container">
        <div class="card text-center" style="max-width:640px;margin:0 auto">
            <div style="font-size:3rem" aria-hidden="true">🎉</div>
            <h1>Спасибо за заказ!</h1>
            <p>Номер вашего заказа — <strong>№ <?= e($order['order_number']) ?></strong>. Мы отправили подтверждение на <?= e($order['customer_email']) ?>.</p>

            <table class="cart-table" style="text-align:left;margin:var(--space-5) 0">
                <thead>
                    <tr><th>Товар</th><th>Кол-во</th><th>Сумма</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td data-label="Товар"><?= e($item['product_name']) ?></td>
                            <td data-label="Кол-во"><?= (int) $item['qty'] ?></td>
                            <td data-label="Сумма"><?= price((float) $item['line_total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-summary__row cart-summary__row--total" style="margin-bottom:var(--space-5)">
                <span>Итого</span>
                <span><?= price((float) $order['total']) ?></span>
            </div>

            <p class="text-muted">Адрес доставки: <?= e($order['shipping_address']) ?></p>

            <div class="cluster" style="justify-content:center">
                <a class="btn" href="<?= e(url('/catalog')) ?>">Продолжить покупки</a>
                <?php if ($auth->check()): ?>
                    <a class="btn btn--outline" href="<?= e(url('/account/orders')) ?>">Мои заказы</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
