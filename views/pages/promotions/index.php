<?php
/**
 * @var array $activePromotions
 * @var array $completedPromotions
 */
?>
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> / <span aria-current="page">Акции</span>
</nav>

<section class="section section--tight">
    <div class="container">
        <span class="eyebrow">Спецпредложения</span>
        <h1>Акции магазина</h1>
        <p class="text-muted" style="max-width:60ch">Следите за скидками на сезонные категории — обновляем предложения регулярно.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($activePromotions === []): ?>
            <div class="empty-state">
                <h3>Сейчас активных акций нет</h3>
                <p>Загляните позже или посмотрите весь каталог.</p>
                <a class="btn" href="<?= e(url('/catalog')) ?>">В каталог</a>
            </div>
        <?php else: ?>
            <div class="grid grid--3">
                <?php foreach ($activePromotions as $promo): ?>
                    <article class="promo-card">
                        <div class="promo-card__media">
                            <span class="promo-card__discount">−<?= (int) $promo['discount_percent'] ?>%</span>
                            <img src="<?= e(url($promo['image_path'] ?? '/assets/img/promotions/promo-01.svg')) ?>" alt="<?= e($promo['title']) ?>" loading="lazy" width="480" height="270">
                        </div>
                        <div class="promo-card__body">
                            <h3><a href="<?= e(url('/promotions/' . $promo['slug'])) ?>"><?= e($promo['title']) ?></a></h3>
                            <p class="text-muted"><?= e(mb_strimwidth((string) $promo['description'], 0, 140, '…')) ?></p>
                            <p class="promo-card__period">Действует до <?= date('d.m.Y', strtotime((string) $promo['ends_at'])) ?></p>
                            <a class="btn btn--outline" href="<?= e(url('/promotions/' . $promo['slug'])) ?>">Подробнее</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($completedPromotions !== []): ?>
<section class="section section--muted">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">Архив</span>
                <h2>Завершённые акции</h2>
            </div>
        </div>
        <div class="grid grid--3">
            <?php foreach ($completedPromotions as $promo): ?>
                <article class="promo-card promo-card--done">
                    <div class="promo-card__media">
                        <span class="badge" style="position:absolute;top:var(--space-3);right:var(--space-3)">Завершена</span>
                        <img src="<?= e(url($promo['image_path'] ?? '/assets/img/promotions/promo-01.svg')) ?>" alt="<?= e($promo['title']) ?>" loading="lazy" width="480" height="270">
                    </div>
                    <div class="promo-card__body">
                        <h3><a href="<?= e(url('/promotions/' . $promo['slug'])) ?>"><?= e($promo['title']) ?></a></h3>
                        <p class="promo-card__period">Действовала до <?= date('d.m.Y', strtotime((string) $promo['ends_at'])) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
