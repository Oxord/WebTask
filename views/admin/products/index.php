<?php
/**
 * @var array $items
 * @var int $total
 * @var int $pages
 * @var int $page
 * @var string $search
 * @var int $categoryId
 * @var array $categories
 * @var \App\Core\Auth $auth
 */

use App\Core\Csrf;
use App\Core\View;
?>
<div class="page-head">
    <h1>Товары</h1>
    <p class="page-head__subtitle">Всего товаров: <?= (int) $total ?></p>
    <a class="btn btn--primary" href="<?= e(url('/admin/products/create')) ?>">+ Добавить товар</a>
</div>

<form class="filters" method="get" action="<?= e(url('/admin/products')) ?>">
    <div class="filters__field">
        <label for="q">Поиск</label>
        <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Название или адрес товара">
    </div>
    <div class="filters__field">
        <label for="category_id">Категория</label>
        <select id="category_id" name="category_id" data-autosubmit>
            <option value="0">Все категории</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>"<?= $categoryId === (int) $category['id'] ? ' selected' : '' ?>>
                    <?= e($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn--secondary">Применить</button>
</form>

<?php if ($items === []): ?>
    <p class="empty-state">Товары не найдены.</p>
<?php else: ?>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr>
                <th>Фото</th>
                <th>Название</th>
                <th>Категория</th>
                <th>Цена</th>
                <th>Остаток</th>
                <th>Статус</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $product): ?>
                <tr>
                    <td>
                        <?php if (!empty($product['image_path'])): ?>
                            <img class="table-thumb" src="<?= e(url($product['image_path'])) ?>" alt="">
                        <?php else: ?>
                            <span class="table-thumb table-thumb--empty">Нет фото</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= e(url('/admin/products/' . $product['id'] . '/edit')) ?>"><?= e($product['name']) ?></a>
                    </td>
                    <td><?= e($categoriesById[(int) $product['category_id']] ?? '—') ?></td>
                    <td><?= e(price((float) $product['price'])) ?></td>
                    <td><?= (int) $product['stock'] ?></td>
                    <td>
                        <span class="badge badge--<?= (int) $product['is_active'] === 1 ? 'success' : 'muted' ?>">
                            <?= (int) $product['is_active'] === 1 ? 'Активен' : 'Скрыт' ?>
                        </span>
                        <?php if ((int) $product['is_featured'] === 1): ?>
                            <span class="badge badge--warning">Рекомендуемый</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-actions">
                        <a class="btn btn--sm btn--secondary" href="<?= e(url('/admin/products/' . $product['id'] . '/edit')) ?>">Изменить</a>
                        <form method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/delete')) ?>" data-confirm="Удалить товар «<?= e($product['name']) ?>»?">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn--sm btn--danger">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= View::partial('admin/partials/pagination', [
        'page' => $page,
        'pages' => $pages,
        'basePath' => '/admin/products',
        'query' => array_filter(['q' => $search, 'category_id' => $categoryId ?: null]),
    ]) ?>
<?php endif; ?>
