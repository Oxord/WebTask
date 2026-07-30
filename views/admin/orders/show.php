<?php
/**
 * @var array $order
 * @var array $statuses
 * @var \App\Core\Auth $auth
 */

use App\Core\Csrf;

$statusLabels = [
    'new' => 'Новый',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'completed' => 'Выполнен',
    'cancelled' => 'Отменён',
];
?>
<div class="page-head">
    <h1>Заказ №<?= e($order['order_number']) ?></h1>
    <a class="btn btn--ghost" href="<?= e(url('/admin/orders')) ?>">← К списку заказов</a>
</div>

<div class="order-grid">
    <section class="panel">
        <h2 class="panel__title">Состав заказа</h2>
        <div class="table-scroll">
            <table class="table">
                <thead>
                <tr>
                    <th>Товар</th>
                    <th>Цена</th>
                    <th>Скидка</th>
                    <th>Кол-во</th>
                    <th>Сумма</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?></td>
                        <td><?= e(price((float) $item['unit_price'])) ?></td>
                        <td><?= (int) $item['discount_percent'] ?>%</td>
                        <td><?= (int) $item['qty'] ?></td>
                        <td><?= e(price((float) $item['line_total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <dl class="order-totals">
            <div><dt>Подытог</dt><dd><?= e(price((float) $order['subtotal'])) ?></dd></div>
            <div><dt>Скидка</dt><dd><?= e(price((float) $order['discount_total'])) ?></dd></div>
            <div class="order-totals__final"><dt>Итого</dt><dd><?= e(price((float) $order['total'])) ?></dd></div>
        </dl>
    </section>

    <section class="panel">
        <h2 class="panel__title">Статус</h2>

        <?php if ($auth->can('orders.manage')): ?>
            <form class="form form--inline" method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/status')) ?>">
                <?= Csrf::field() ?>
                <div class="form-field">
                    <label for="status">Новый статус</label>
                    <select id="status" name="status">
                        <?php foreach ($statuses as $value): ?>
                            <option value="<?= e($value) ?>"<?= $order['status'] === $value ? ' selected' : '' ?>><?= e($statusLabels[$value] ?? $value) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn--primary">Обновить статус</button>
            </form>
        <?php else: ?>
            <p><span class="badge badge--status-<?= e($order['status']) ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></p>
            <p class="form-hint">У вас нет прав на изменение статуса заказа.</p>
        <?php endif; ?>

        <h2 class="panel__title">Покупатель</h2>
        <dl class="details-list">
            <div><dt>Имя</dt><dd><?= e($order['customer_name']) ?></dd></div>
            <div><dt>Email</dt><dd><?= e($order['customer_email']) ?></dd></div>
            <div><dt>Телефон</dt><dd><?= e($order['customer_phone']) ?></dd></div>
            <div><dt>Адрес доставки</dt><dd><?= e($order['shipping_address']) ?></dd></div>
            <?php if (!empty($order['comment'])): ?>
                <div><dt>Комментарий</dt><dd><?= nl2br(e($order['comment'])) ?></dd></div>
            <?php endif; ?>
            <div><dt>Дата оформления</dt><dd><?= e(date('d.m.Y H:i', strtotime($order['created_at']))) ?></dd></div>
        </dl>
    </section>
</div>
