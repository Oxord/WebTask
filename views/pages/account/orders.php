<?php
/** @var array $orders */

use App\Core\View;
?>
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> /
    <a href="<?= e(url('/account')) ?>">Личный кабинет</a> /
    <span aria-current="page">Мои заказы</span>
</nav>

<section class="section">
    <div class="container">
        <h1>Мои заказы</h1>

        <div class="account-layout">
            <?= View::partial('partials/account-nav', ['active' => 'orders']) ?>

            <div>
                <?php if ($orders === []): ?>
                    <div class="empty-state">
                        <h3>У вас пока нет заказов</h3>
                        <p>Самое время выбрать что-нибудь уютное.</p>
                        <a class="btn" href="<?= e(url('/catalog')) ?>">Перейти в каталог</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-row">
                            <div class="order-row__meta">
                                <strong><a href="<?= e(url('/account/orders/' . $order['id'])) ?>">№ <?= e($order['order_number']) ?></a></strong>
                                <span class="text-muted"><?= date('d.m.Y H:i', strtotime((string) $order['created_at'])) ?></span>
                            </div>
                            <span><?= price((float) $order['total']) ?></span>
                            <?= View::partial('partials/order-status', ['status' => $order['status']]) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
