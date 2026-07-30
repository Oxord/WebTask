<?php
/**
 * @var array $items
 * @var int $total
 * @var int $pages
 * @var int $page
 * @var string $status
 * @var array $counts
 */

use App\Core\Csrf;
use App\Core\View;

$statusLabels = ['pending' => 'Ожидают', 'approved' => 'Одобрены', 'rejected' => 'Отклонены'];
$ratingStars = static fn (int $rating): string => str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
?>
<div class="page-head">
    <h1>Модерация отзывов</h1>
    <p class="page-head__subtitle">
        На модерации: <span class="badge badge--warning"><?= (int) ($counts['pending'] ?? 0) ?></span>
        Одобрено: <span class="badge badge--success"><?= (int) ($counts['approved'] ?? 0) ?></span>
        Отклонено: <span class="badge badge--muted"><?= (int) ($counts['rejected'] ?? 0) ?></span>
    </p>
</div>

<form class="filters" method="get" action="<?= e(url('/admin/reviews')) ?>">
    <div class="filters__field">
        <label for="status">Статус</label>
        <select id="status" name="status" data-autosubmit>
            <option value="">Все</option>
            <?php foreach ($statusLabels as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= $status === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if ($items === []): ?>
    <p class="empty-state">Отзывов не найдено.</p>
<?php else: ?>
    <div class="review-list">
        <?php foreach ($items as $review): ?>
            <article class="review-card">
                <header class="review-card__head">
                    <div>
                        <strong><?= e($review['author_name']) ?></strong>
                        <?php if ($review['product_name'] !== null): ?>
                            <span class="review-card__product">— <?= e($review['product_name']) ?></span>
                        <?php else: ?>
                            <span class="review-card__product">— отзыв о магазине</span>
                        <?php endif; ?>
                    </div>
                    <span class="review-card__rating" aria-label="Оценка <?= (int) $review['rating'] ?> из 5"><?= $ratingStars((int) $review['rating']) ?></span>
                </header>

                <p class="review-card__body"><?= nl2br(e($review['body'])) ?></p>

                <footer class="review-card__foot">
                    <div class="review-card__meta">
                        <span class="badge badge--status-<?= e($review['status']) ?>"><?= e($statusLabels[$review['status']] ?? $review['status']) ?></span>
                        <?php if ((int) $review['is_featured'] === 1): ?>
                            <span class="badge badge--warning">Избранный</span>
                        <?php endif; ?>
                        <time datetime="<?= e($review['created_at']) ?>"><?= e(date('d.m.Y H:i', strtotime($review['created_at']))) ?></time>
                    </div>

                    <div class="review-card__actions">
                        <?php if ($review['status'] !== 'approved'): ?>
                            <form method="post" action="<?= e(url('/admin/reviews/' . $review['id'] . '/moderate')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn--sm btn--success">Одобрить</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($review['status'] !== 'rejected'): ?>
                            <form method="post" action="<?= e(url('/admin/reviews/' . $review['id'] . '/moderate')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn--sm btn--secondary">Отклонить</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($review['status'] === 'approved'): ?>
                            <form method="post" action="<?= e(url('/admin/reviews/' . $review['id'] . '/featured')) ?>">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn btn--sm btn--secondary">
                                    <?= (int) $review['is_featured'] === 1 ? 'Убрать из избранного' : 'В избранное' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="<?= e(url('/admin/reviews/' . $review['id'] . '/delete')) ?>" data-confirm="Удалить отзыв безвозвратно?">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn--sm btn--danger">Удалить</button>
                        </form>
                    </div>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>

    <?= View::partial('admin/partials/pagination', [
        'page' => $page,
        'pages' => $pages,
        'basePath' => '/admin/reviews',
        'query' => array_filter(['status' => $status]),
    ]) ?>
<?php endif; ?>
