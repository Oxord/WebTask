<?php
/**
 * @var int $page
 * @var int $pages
 * @var string $basePath  Путь без query-строки, например /admin/products
 * @var array $query      Текущие GET-параметры фильтров (без page)
 */

if ($pages <= 1) {
    return;
}

$buildUrl = static function (int $targetPage) use ($basePath, $query): string {
    $params = $query;
    $params['page'] = $targetPage;

    return url($basePath) . '?' . http_build_query($params);
};
?>
<nav class="pagination" aria-label="Страницы">
    <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>"
       href="<?= $page <= 1 ? '#' : e($buildUrl($page - 1)) ?>"
       aria-disabled="<?= $page <= 1 ? 'true' : 'false' ?>">← Назад</a>

    <span class="pagination__status">Страница <?= (int) $page ?> из <?= (int) $pages ?></span>

    <a class="pagination__link<?= $page >= $pages ? ' is-disabled' : '' ?>"
       href="<?= $page >= $pages ? '#' : e($buildUrl($page + 1)) ?>"
       aria-disabled="<?= $page >= $pages ? 'true' : 'false' ?>">Вперёд →</a>
</nav>
