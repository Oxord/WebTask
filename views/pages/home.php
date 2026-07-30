<?php
/**
 * @var array $featuredProducts
 * @var array $activePromotions
 * @var array $featuredReviews Отзывы уже с готовым $review['author'] (см. HomeController)
 */

$heroImages = ['hero-1.svg', 'hero-2.svg', 'hero-3.svg'];
$heroSlides = [
    [
        'image' => $heroImages[0],
        'eyebrow' => 'Новая коллекция',
        'title' => 'Уют начинается с деталей',
        'text' => 'Текстиль, керамика и свет для интерьера, в котором хочется остаться подольше.',
        'cta' => '/catalog',
    ],
    [
        'image' => $heroImages[1],
        'eyebrow' => 'Скидки уже действуют',
        'title' => 'Сезонные акции на декор',
        'text' => 'Следите за актуальными предложениями — скидки на пледы, свет и ароматы для дома.',
        'cta' => '/promotions',
    ],
    [
        'image' => $heroImages[2],
        'eyebrow' => 'Ручная работа',
        'title' => 'Вещи с характером',
        'text' => 'Керамика, плетение и текстиль от мастеров — каждая вещь немного не похожа на другие.',
        'cta' => '/catalog',
    ],
];
?>
<section class="section section--tight">
    <div class="container">
        <div class="hero-slider" id="heroSlider" aria-roledescription="карусель" aria-label="Акции и новинки">
            <div class="hero-slider__track" id="heroSliderTrack">
                <?php foreach ($heroSlides as $i => $slide): ?>
                    <div class="hero-slide" role="group" aria-roledescription="слайд" aria-label="<?= $i + 1 ?> из <?= count($heroSlides) ?>">
                        <img class="hero-slide__img" src="<?= e(asset('img/' . $slide['image'])) ?>" alt=""
                             loading="<?= $i === 0 ? 'eager' : 'lazy' ?>" width="1200" height="420">
                        <div class="hero-slide__caption">
                            <span class="badge badge--sale"><?= e($slide['eyebrow']) ?></span>
                            <h2><?= e($slide['title']) ?></h2>
                            <p><?= e($slide['text']) ?></p>
                            <a class="btn" href="<?= e(url($slide['cta'])) ?>">Смотреть</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="hero-slider__arrow hero-slider__arrow--prev" id="heroPrev" aria-label="Предыдущий слайд">‹</button>
            <button type="button" class="hero-slider__arrow hero-slider__arrow--next" id="heroNext" aria-label="Следующий слайд">›</button>

            <div class="hero-slider__dots" id="heroDots">
                <?php foreach ($heroSlides as $i => $slide): ?>
                    <button type="button" class="hero-slider__dot<?= $i === 0 ? ' is-active' : '' ?>" data-slide="<?= $i ?>" aria-label="Перейти к слайду <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="section" id="about">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">О нас</span>
                <h2>Делаем дом теплее</h2>
                <p>Мы собираем предметы для интерьера, которые легко вписать в любую квартиру: спокойные цвета, натуральные материалы, аккуратная работа мастеров.</p>
            </div>
        </div>
        <div class="grid grid--4">
            <div class="perk-card">
                <div class="perk-card__icon" aria-hidden="true">🎯</div>
                <h3>Наша миссия</h3>
                <p>Помогать создавать уютные интерьеры без переплат за бренд — только качественные вещи по честной цене.</p>
            </div>
            <div class="perk-card">
                <div class="perk-card__icon" aria-hidden="true">🚚</div>
                <h3>Быстрая доставка</h3>
                <p>Отправляем заказы по всей России, бережно упаковываем хрупкие товары.</p>
            </div>
            <div class="perk-card">
                <div class="perk-card__icon" aria-hidden="true">🌿</div>
                <h3>Натуральные материалы</h3>
                <p>Керамика, лён, ротанг и дерево — выбираем поставщиков, которым доверяем сами.</p>
            </div>
            <div class="perk-card">
                <div class="perk-card__icon" aria-hidden="true">💬</div>
                <h3>Живая поддержка</h3>
                <p>Отвечаем на вопросы о товарах и заказе быстро — без роботов и долгих ожиданий.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--muted">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">Рекомендуем</span>
                <h2>Популярные товары</h2>
            </div>
            <a class="btn btn--outline" href="<?= e(url('/catalog')) ?>">Весь каталог</a>
        </div>
        <?php if ($featuredProducts === []): ?>
            <p class="text-muted">Скоро здесь появятся рекомендованные товары.</p>
        <?php else: ?>
            <div class="grid grid--4">
                <?php foreach ($featuredProducts as $product): ?>
                    <?= \App\Core\View::partial('partials/product-card', ['product' => $product]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section" id="promotions">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">Специальные предложения</span>
                <h2>Акции сейчас</h2>
            </div>
            <a class="btn btn--outline" href="<?= e(url('/promotions')) ?>">Все акции</a>
        </div>
        <?php if ($activePromotions === []): ?>
            <p class="text-muted">Сейчас активных акций нет — загляните позже.</p>
        <?php else: ?>
            <div class="grid grid--3">
                <?php foreach (array_slice($activePromotions, 0, 3) as $promo): ?>
                    <article class="promo-card">
                        <div class="promo-card__media">
                            <span class="promo-card__discount">−<?= (int) $promo['discount_percent'] ?>%</span>
                            <img src="<?= e(url($promo['image_path'] ?? '/assets/img/promotions/promo-01.svg')) ?>" alt="<?= e($promo['title']) ?>" loading="lazy" width="480" height="270">
                        </div>
                        <div class="promo-card__body">
                            <h3><a href="<?= e(url('/promotions/' . $promo['slug'])) ?>"><?= e($promo['title']) ?></a></h3>
                            <p class="text-muted"><?= e(mb_strimwidth((string) $promo['description'], 0, 120, '…')) ?></p>
                            <p class="promo-card__period">До <?= date('d.m.Y', strtotime((string) $promo['ends_at'])) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--muted">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">Отзывы</span>
                <h2>Что говорят покупатели</h2>
            </div>
            <a class="btn btn--outline" href="<?= e(url('/reviews')) ?>">Все отзывы</a>
        </div>
        <?php if ($featuredReviews === []): ?>
            <p class="text-muted">Отзывы скоро появятся — станьте первым, кто поделится впечатлением.</p>
        <?php else: ?>
            <div class="grid grid--3">
                <?php foreach ($featuredReviews as $review): ?>
                    <article class="review-card">
                        <div class="review-card__head">
                            <span class="review-card__author"><?= e($review['author']['full_name'] ?? 'Покупатель') ?></span>
                            <span class="rating"><span class="rating__stars"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></span></span>
                        </div>
                        <p class="review-card__body"><?= e(mb_strimwidth((string) $review['body'], 0, 220, '…')) ?></p>
                        <span class="review-card__date"><?= date('d.m.Y', strtotime((string) $review['created_at'])) ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="cta">
            <h2>Не знаете, что выбрать?</h2>
            <p>Расскажите, какой интерьер вы хотите получить — подскажем товары и поможем с подбором декора.</p>
            <a class="btn" href="<?= e(url('/contacts')) ?>">Связаться с нами</a>
            <div class="cta__contacts">
                <span>📞 +7 (495) 123-45-67</span>
                <span>✉️ hello@decor-home.ru</span>
                <span>🕒 Пн–Вс, 9:00–21:00</span>
            </div>
        </div>
    </div>
</section>
