<?php
/**
 * @var array $product
 * @var array|null $category
 * @var array $reviews
 * @var float|null $averageRating
 * @var \App\Core\Auth $auth
 * @var bool $canReview
 * @var string|null $reviewBlockReason
 * @var array $errors
 */

use App\Core\Csrf;

$discount = (int) ($product['discount_percent'] ?? 0);
$finalPrice = (float) ($product['final_price'] ?? $product['price']);
$stock = (int) $product['stock'];
$inStock = $stock > 0;
?>
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> /
    <a href="<?= e(url('/catalog')) ?>">Каталог</a>
    <?php if ($category): ?>
        / <a href="<?= e(url('/catalog?category=' . (int) $category['id'])) ?>"><?= e($category['name']) ?></a>
    <?php endif; ?>
    / <span aria-current="page"><?= e($product['name']) ?></span>
</nav>

<section class="section">
    <div class="container">
        <div class="product-view">
            <div class="product-gallery">
                <img src="<?= e(url($product['image_path'] ?? '/assets/img/products/product-01.svg')) ?>"
                     alt="<?= e($product['name']) ?>" width="600" height="600">
            </div>

            <div class="product-info">
                <?php if ($category): ?>
                    <p class="product-info__cat"><?= e($category['name']) ?></p>
                <?php endif; ?>
                <h1><?= e($product['name']) ?></h1>

                <?php if ($averageRating !== null): ?>
                    <p class="rating">
                        <span class="rating__stars"><?= str_repeat('★', (int) round($averageRating)) . str_repeat('☆', 5 - (int) round($averageRating)) ?></span>
                        <span><?= number_format($averageRating, 1) ?> из 5 (<?= count($reviews) ?> отзывов)</span>
                    </p>
                <?php endif; ?>

                <p><?= nl2br(e((string) $product['description'])) ?></p>

                <div class="product-info__price">
                    <?php if ($discount > 0): ?>
                        <span class="price-old"><?= price((float) $product['price']) ?></span>
                        <span class="price-new"><?= price($finalPrice) ?></span>
                        <span class="badge badge--sale">−<?= $discount ?>%</span>
                    <?php else: ?>
                        <span class="price-new"><?= price($finalPrice) ?></span>
                    <?php endif; ?>
                </div>

                <p class="stock <?= $inStock ? 'stock--in' : 'stock--out' ?>">
                    <?= $inStock ? 'В наличии: ' . $stock . ' шт.' : 'Нет в наличии' ?>
                </p>

                <?php if ($inStock): ?>
                    <form action="<?= e(url('/cart/add')) ?>" method="post" class="product-actions js-add-to-cart-form">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <label class="visually-hidden" for="qty">Количество</label>
                        <span class="qty-control">
                            <button type="button" class="js-qty-minus" aria-label="Уменьшить количество">−</button>
                            <input type="number" id="qty" name="qty" value="1" min="1" max="<?= $stock ?>" inputmode="numeric">
                            <button type="button" class="js-qty-plus" aria-label="Увеличить количество">+</button>
                        </span>
                        <button type="submit" class="btn js-add-to-cart">В корзину</button>
                    </form>
                <?php else: ?>
                    <p><button type="button" class="btn" disabled aria-disabled="true">Нет в наличии</button></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="product-tabs-section">
            <h2>Отзывы о товаре</h2>

            <?php if ($reviews === []): ?>
                <p class="text-muted">Пока никто не оставил отзыв об этом товаре.</p>
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
            <?php endif; ?>

            <div class="card" style="margin-top:var(--space-6)">
                <h3>Оставить отзыв</h3>
                <?php if (!$auth->check()): ?>
                    <p class="text-muted">Чтобы оставить отзыв, пожалуйста, <a href="<?= e(url('/login?redirect=' . rawurlencode('/catalog/' . $product['slug']))) ?>">войдите в аккаунт</a>.</p>
                <?php elseif (!$canReview): ?>
                    <p class="text-muted"><?= e($reviewBlockReason ?? 'Отзыв пока недоступен.') ?></p>
                <?php else: ?>
                    <form action="<?= e(url('/reviews')) ?>" method="post" class="stack">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <input type="hidden" name="redirect" value="/catalog/<?= e($product['slug']) ?>">
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
                            <textarea id="body" name="body" required minlength="10" maxlength="2000" placeholder="Расскажите, понравился ли товар…"><?= e((string) old('body')) ?></textarea>
                            <?php if (isset($errors['body'])): ?><p class="field-error"><?= e($errors['body'][0]) ?></p><?php endif; ?>
                        </div>
                        <button type="submit" class="btn">Отправить отзыв</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
