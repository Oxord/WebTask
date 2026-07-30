<?php
/**
 * @var array $reviews
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var float|null $averageRating
 * @var int $totalApproved
 * @var \App\Core\Auth $auth
 * @var bool $canReview
 * @var string|null $reviewBlockReason
 * @var array $errors
 */

use App\Core\Csrf;
use App\Core\View;
?>
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> / <span aria-current="page">Отзывы</span>
</nav>

<section class="section section--tight">
    <div class="container">
        <span class="eyebrow">Отзывы покупателей</span>
        <h1>Что говорят о нас</h1>
        <?php if ($averageRating !== null): ?>
            <p class="rating" style="font-size:1.1rem">
                <span class="rating__stars"><?= str_repeat('★', (int) round($averageRating)) . str_repeat('☆', 5 - (int) round($averageRating)) ?></span>
                <span><?= number_format($averageRating, 1) ?> из 5 — <?= (int) $totalApproved ?> отзывов</span>
            </p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="catalog-layout" style="grid-template-columns:1fr">
            <?php if ($reviews === []): ?>
                <div class="empty-state">
                    <h3>Отзывов пока нет</h3>
                    <p>Станьте первым, кто поделится впечатлением о покупке.</p>
                </div>
            <?php else: ?>
                <div class="grid grid--3">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-card">
                            <div class="review-card__head">
                                <span class="review-card__author"><?= e($review['author']['full_name'] ?? 'Покупатель') ?></span>
                                <span class="rating"><span class="rating__stars"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></span></span>
                            </div>
                            <p class="review-card__body"><?= e($review['body']) ?></p>
                            <span class="review-card__date"><?= date('d.m.Y', strtotime((string) $review['created_at'])) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?= View::partial('partials/pagination', ['page' => $page, 'pages' => $pages, 'baseUrl' => url('/reviews') . '?']) ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section section--muted">
    <div class="container">
        <div class="card" style="max-width:640px;margin:0 auto">
            <h2>Оставить отзыв</h2>
            <?php if (!$auth->check()): ?>
                <p class="text-muted">Отзывы могут оставлять только покупатели с завершённым заказом. <a href="<?= e(url('/login?redirect=' . rawurlencode('/reviews'))) ?>">Войдите в аккаунт</a>, чтобы поделиться впечатлением.</p>
                <a class="btn btn--outline" href="<?= e(url('/register')) ?>">Зарегистрироваться</a>
            <?php elseif (!$canReview): ?>
                <p class="text-muted"><?= e($reviewBlockReason ?? 'Отзыв пока недоступен.') ?></p>
            <?php else: ?>
                <form action="<?= e(url('/reviews')) ?>" method="post" class="stack">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="redirect" value="/reviews">
                    <div class="field<?= isset($errors['rating']) ? ' field--error' : '' ?>">
                        <label for="rating">Оценка</label>
                        <select id="rating" name="rating" required>
                            <option value="5">5 — отлично</option>
                            <option value="4">4 — хорошо</option>
                            <option value="3">3 — нормально</option>
                            <option value="2">2 — не понравилось</option>
                            <option value="1">1 — плохо</option>
                        </select>
                        <?php if (isset($errors['rating'])): ?><p class="field-error"><?= e($errors['rating'][0]) ?></p><?php endif; ?>
                    </div>
                    <div class="field<?= isset($errors['body']) ? ' field--error' : '' ?>">
                        <label for="body">Ваш отзыв</label>
                        <textarea id="body" name="body" required minlength="10" maxlength="2000" placeholder="Расскажите о своём опыте покупки…"><?= e((string) old('body')) ?></textarea>
                        <?php if (isset($errors['body'])): ?><p class="field-error"><?= e($errors['body'][0]) ?></p><?php endif; ?>
                    </div>
                    <button type="submit" class="btn">Отправить отзыв</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
