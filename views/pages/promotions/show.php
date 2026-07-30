<?php
/**
 * @var array $promotion
 * @var array $products
 * @var bool $isActive
 */

use App\Core\View;
?>
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> /
    <a href="<?= e(url('/promotions')) ?>">Акции</a> /
    <span aria-current="page"><?= e($promotion['title']) ?></span>
</nav>

<section class="section section--tight">
    <div class="container">
        <div class="promo-card" style="flex-direction:row-reverse;flex-wrap:wrap">
            <div class="promo-card__media" style="flex:1 1 320px">
                <span class="promo-card__discount">−<?= (int) $promotion['discount_percent'] ?>%</span>
                <img src="<?= e(url($promotion['image_path'] ?? '/assets/img/promotions/promo-01.svg')) ?>" alt="<?= e($promotion['title']) ?>" width="640" height="360">
            </div>
            <div class="promo-card__body" style="flex:1 1 320px">
                <?php if (!$isActive): ?>
                    <span class="badge">Акция завершена</span>
                <?php endif; ?>
                <h1><?= e($promotion['title']) ?></h1>
                <p><?= nl2br(e((string) $promotion['description'])) ?></p>
                <p class="promo-card__period">
                    <?= $isActive ? 'Действует' : 'Действовала' ?>:
                    <?= date('d.m.Y', strtotime((string) $promotion['starts_at'])) ?> — <?= date('d.m.Y', strtotime((string) $promotion['ends_at'])) ?>
                </p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">В акции участвуют</span>
                <h2>Товары по акции</h2>
            </div>
        </div>
        <?php if ($products === []): ?>
            <p class="text-muted">Товары этой акции больше недоступны.</p>
        <?php else: ?>
            <div class="grid grid--3">
                <?php foreach ($products as $product): ?>
                    <?= View::partial('partials/product-card', ['product' => $product]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
