<?php
/**
 * @var array $stats
 * @var \App\Core\Auth $auth
 */

$isAdmin = $auth->isAdmin();
$revenueDays = $stats['revenue_by_day'];
$maxRevenue = 0.0;
foreach ($revenueDays as $day) {
    $maxRevenue = max($maxRevenue, $day['revenue']);
}
$dayCount = count($revenueDays);

$statusLabels = [
    'new' => 'Новый',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'completed' => 'Выполнен',
    'cancelled' => 'Отменён',
];

$quickLinks = [
    ['/admin/products', 'Товары', 'products.manage'],
    ['/admin/categories', 'Категории', 'categories.manage'],
    ['/admin/promotions', 'Акции', 'promotions.manage'],
    ['/admin/reviews', 'Отзывы на модерации', 'reviews.moderate'],
    ['/admin/orders', 'Заказы', 'orders.view'],
    ['/admin/users', 'Пользователи', 'users.manage'],
    ['/admin/messages', 'Сообщения', 'admin.access'],
];
?>
<div class="page-head">
    <h1>Дашборд</h1>
    <p class="page-head__subtitle">Сводка за последние 30 дней</p>
</div>

<div class="tiles">
    <div class="tile">
        <span class="tile__label">Заказов за 30 дней</span>
        <span class="tile__value"><?= (int) $stats['total_orders'] ?></span>
    </div>

    <?php if ($isAdmin): ?>
        <div class="tile">
            <span class="tile__label">Выручка за 30 дней</span>
            <span class="tile__value"><?= e(price((float) $stats['total_revenue'])) ?></span>
        </div>
        <div class="tile">
            <span class="tile__label">Средний чек</span>
            <span class="tile__value"><?= e(price((float) $stats['average_order_value'])) ?></span>
        </div>
        <div class="tile">
            <span class="tile__label">Новые клиенты</span>
            <span class="tile__value"><?= (int) $stats['new_customers'] ?></span>
        </div>
        <div class="tile">
            <span class="tile__label">Товаров в каталоге</span>
            <span class="tile__value"><?= (int) $stats['total_products'] ?></span>
        </div>
    <?php endif; ?>

    <div class="tile tile--accent">
        <span class="tile__label">Отзывов на модерации</span>
        <span class="tile__value"><?= (int) $stats['pending_reviews'] ?></span>
    </div>
    <div class="tile tile--accent">
        <span class="tile__label">Непрочитанных сообщений</span>
        <span class="tile__value"><?= (int) $stats['unread_messages'] ?></span>
    </div>
</div>

<?php if ($isAdmin): ?>
    <section class="panel">
        <h2 class="panel__title">Выручка по дням</h2>
        <div class="chart" data-chart>
            <div class="chart-bars">
                <?php foreach ($revenueDays as $i => $day):
                    $height = $maxRevenue > 0 ? max(2, round($day['revenue'] / $maxRevenue * 100)) : 2;
                    $label = date('d.m', strtotime($day['date']));
                    $showLabel = $i % 5 === 0 || $i === $dayCount - 1;
                ?>
                    <div class="chart-bar"
                         tabindex="0"
                         style="--h: <?= (int) $height ?>%"
                         data-date="<?= e(date('d.m.Y', strtotime($day['date']))) ?>"
                         data-revenue="<?= e(price((float) $day['revenue'])) ?>"
                         data-orders="<?= (int) $day['orders'] ?>">
                        <span class="chart-bar__fill"></span>
                        <span class="chart-bar__axis"><?= $showLabel ? e($label) : '' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h2 class="panel__title">Топ-5 товаров по выручке</h2>
        <?php if ($stats['top_products'] === []): ?>
            <p class="empty-state">Пока нет продаж за выбранный период.</p>
        <?php else: ?>
            <div class="table-scroll">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Продано, шт.</th>
                        <th>Выручка</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($stats['top_products'] as $product): ?>
                        <tr>
                            <td><?= e($product['product_name']) ?></td>
                            <td><?= (int) $product['qty'] ?></td>
                            <td><?= e(price((float) $product['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="panel">
    <h2 class="panel__title">Заказы по статусам</h2>
    <?php if ($stats['orders_by_status'] === []): ?>
        <p class="empty-state">Заказов за период ещё не было.</p>
    <?php else: ?>
        <div class="status-grid">
            <?php foreach ($stats['orders_by_status'] as $status => $count): ?>
                <div class="status-grid__item">
                    <span class="badge badge--status-<?= e($status) ?>"><?= e($statusLabels[$status] ?? $status) ?></span>
                    <span class="status-grid__count"><?= (int) $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Быстрые ссылки</h2>
    <div class="quick-links">
        <?php foreach ($quickLinks as [$path, $label, $perm]): ?>
            <?php if ($auth->can($perm)): ?>
                <a class="quick-link" href="<?= e(url($path)) ?>"><?= e($label) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
