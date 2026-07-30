<?php
/**
 * @var array $items
 */

use App\Core\Csrf;

$now = time();
?>
<div class="page-head">
    <h1>Акции</h1>
    <p class="page-head__subtitle">Всего акций: <?= count($items) ?></p>
    <a class="btn btn--primary" href="<?= e(url('/admin/promotions/create')) ?>">+ Новая акция</a>
</div>

<?php if ($items === []): ?>
    <p class="empty-state">Акции ещё не созданы.</p>
<?php else: ?>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr>
                <th>Заголовок</th>
                <th>Скидка</th>
                <th>Период действия</th>
                <th>Статус</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $promotion):
                $isRunning = (int) $promotion['is_active'] === 1
                    && strtotime($promotion['starts_at']) <= $now
                    && strtotime($promotion['ends_at']) >= $now;
            ?>
                <tr>
                    <td><a href="<?= e(url('/admin/promotions/' . $promotion['id'] . '/edit')) ?>"><?= e($promotion['title']) ?></a></td>
                    <td><?= (int) $promotion['discount_percent'] ?>%</td>
                    <td><?= e(date('d.m.Y', strtotime($promotion['starts_at']))) ?> — <?= e(date('d.m.Y', strtotime($promotion['ends_at']))) ?></td>
                    <td>
                        <?php if (!$promotion['is_active']): ?>
                            <span class="badge badge--muted">Выключена</span>
                        <?php elseif ($isRunning): ?>
                            <span class="badge badge--success">Идёт сейчас</span>
                        <?php else: ?>
                            <span class="badge badge--warning">Запланирована / завершена</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-actions">
                        <a class="btn btn--sm btn--secondary" href="<?= e(url('/admin/promotions/' . $promotion['id'] . '/edit')) ?>">Изменить</a>
                        <form method="post" action="<?= e(url('/admin/promotions/' . $promotion['id'] . '/delete')) ?>" data-confirm="Удалить акцию «<?= e($promotion['title']) ?>»?">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn--sm btn--danger">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
