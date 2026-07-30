<?php
/**
 * @var array $products
 * @var int $total
 * @var int $pages
 * @var int $page
 * @var array $filters
 * @var array $categories
 */

use App\Core\View;

$sortOptions = [
    '' => 'По умолчанию',
    'newest' => 'Сначала новинки',
    'price_asc' => 'Цена: по возрастанию',
    'price_desc' => 'Цена: по убыванию',
    'name' => 'По названию',
];

$paginationQuery = $filters;
unset($paginationQuery['page'], $paginationQuery['per_page']);
$paginationQuery = array_filter($paginationQuery, static fn ($v) => $v !== '' && $v !== 0);
$baseUrl = url('/catalog') . '?' . (http_build_query($paginationQuery) !== '' ? http_build_query($paginationQuery) . '&' : '');
?>
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> / <span aria-current="page">Каталог</span>
</nav>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">Каталог</span>
                <h1>Товары для дома</h1>
            </div>
        </div>

        <div class="catalog-layout">
            <aside>
                <form class="filters" method="get" action="<?= e(url('/catalog')) ?>" role="search" aria-label="Фильтр товаров">
                    <div class="filters__group">
                        <div class="field">
                            <label for="f-q">Поиск</label>
                            <input type="search" id="f-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Название товара…">
                        </div>
                    </div>

                    <div class="filters__group">
                        <div class="field">
                            <label for="f-category">Категория</label>
                            <select id="f-category" name="category">
                                <option value="">Все категории</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === $filters['category_id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filters__group">
                        <div class="field-row field-row--2">
                            <div class="field">
                                <label for="f-price-min">Цена от</label>
                                <input type="number" min="0" step="1" id="f-price-min" name="price_min" value="<?= e($filters['price_min']) ?>">
                            </div>
                            <div class="field">
                                <label for="f-price-max">Цена до</label>
                                <input type="number" min="0" step="1" id="f-price-max" name="price_max" value="<?= e($filters['price_max']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="filters__group stack">
                        <label class="checkbox">
                            <input type="checkbox" name="in_stock" value="1" <?= $filters['in_stock'] ? 'checked' : '' ?>>
                            Только в наличии
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="on_sale" value="1" <?= $filters['on_sale'] ? 'checked' : '' ?>>
                            Только со скидкой
                        </label>
                    </div>

                    <div class="filters__group stack">
                        <button type="submit" class="btn btn--block">Применить</button>
                        <a class="btn btn--ghost btn--block" href="<?= e(url('/catalog')) ?>">Сбросить фильтры</a>
                    </div>
                </form>
            </aside>

            <div>
                <form method="get" action="<?= e(url('/catalog')) ?>" class="results-bar">
                    <span class="results-bar__count"><?= (int) $total ?> товаров найдено</span>
                    <?php foreach (['q', 'category', 'price_min', 'price_max', 'in_stock', 'on_sale'] as $key): ?>
                        <?php $val = $key === 'category' ? $filters['category_id'] : $filters[$key]; ?>
                        <?php if ($val !== '' && $val !== 0): ?>
                            <input type="hidden" name="<?= e($key) ?>" value="<?= e((string) $val) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <label class="sort-select">
                        Сортировка:
                        <select name="sort" onchange="this.form.submit()">
                            <?php foreach ($sortOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $filters['sort'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <noscript><button type="submit" class="btn btn--sm">Применить</button></noscript>
                </form>

                <?php if ($products === []): ?>
                    <div class="empty-state">
                        <h3>По вашему запросу ничего не найдено</h3>
                        <p>Попробуйте изменить фильтры или посмотреть весь каталог.</p>
                        <a class="btn" href="<?= e(url('/catalog')) ?>">Сбросить фильтры</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid--3">
                        <?php foreach ($products as $product): ?>
                            <?= View::partial('partials/product-card', ['product' => $product]) ?>
                        <?php endforeach; ?>
                    </div>
                    <?= View::partial('partials/pagination', ['page' => $page, 'pages' => $pages, 'baseUrl' => $baseUrl]) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
