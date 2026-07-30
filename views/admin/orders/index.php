<?php
/**
 * @var array $items
 * @var int $total
 * @var int $pages
 * @var int $page
 * @var string $status
 * @var array $statuses
 */

use App\Core\View;

$statusLabels = [
    'new' => 'Новый',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'completed' => 'Выполнен',
    'cancelled' => 'Отменён',
];
?>
<div class="page-head">
    <h1>Заказы</h1>
    <p class="page-head__subtitle">Всего заказов: <?= (int) $total ?></p>
</div>

<form class="filters" method="get" action="<?= e(url('/admin/orders')) ?>">
    <div class="filters__field">
        <label for="status">Статус</label>
        <select id="status" name="status" data-autosubmit>
            <option value="">Все статусы</option>
            <?php foreach ($statuses as $value): ?>
                <option value="<?= e($value) ?>"<?= $status === $value ? ' selected' : '' ?>><?= e($statusLabels[$value] ?? $value) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if ($items === []): ?>
    <p class="empty-state">Заказы не найдены.</p>
<?php else: ?>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr>
                <th>№ заказа</th>
                <th>Покупатель</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Дата</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $order): ?>
                <tr>
                    <td><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>"><?= e($order['order_number']) ?></a></td>
                    <td><?= e($order['customer_name']) ?></td>
                    <td><?= e(price((float) $order['total'])) ?></td>
                    <td><span class="badge badge--status-<?= e($order['status']) ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                    <td><?= e(date('d.m.Y H:i', strtotime($order['created_at']))) ?></td>
                    <td class="table-actions">
                        <a class="btn btn--sm btn--secondary" href="<?= e(url('/admin/orders/' . $order['id'])) ?>">Подробнее</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= View::partial('admin/partials/pagination', [
        'page' => $page,
        'pages' => $pages,
        'basePath' => '/admin/orders',
        'query' => array_filter(['status' => $status]),
    ]) ?>
<?php endif; ?>
