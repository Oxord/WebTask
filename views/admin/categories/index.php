<?php
/**
 * @var array $items
 */

use App\Core\Csrf;
?>
<div class="page-head">
    <h1>Категории</h1>
    <p class="page-head__subtitle">Всего категорий: <?= count($items) ?></p>
</div>

<section class="panel">
    <h2 class="panel__title">Новая категория</h2>
    <form class="form form--inline" method="post" action="<?= e(url('/admin/categories')) ?>">
        <?= Csrf::field() ?>

        <div class="form-field">
            <label for="new-name">Название *</label>
            <input type="text" id="new-name" name="name" required maxlength="120" data-slug-name>
        </div>
        <div class="form-field">
            <label for="new-slug">Адрес (slug)</label>
            <input type="text" id="new-slug" name="slug" maxlength="120" data-slug-target>
        </div>
        <div class="form-field">
            <label for="new-sort">Порядок</label>
            <input type="number" id="new-sort" name="sort_order" value="0" step="1">
        </div>
        <div class="form-field form-field--wide">
            <label for="new-description">Описание</label>
            <input type="text" id="new-description" name="description" maxlength="255">
        </div>
        <button type="submit" class="btn btn--primary">Добавить</button>
    </form>
</section>

<?php if ($items === []): ?>
    <p class="empty-state">Категории пока не созданы.</p>
<?php else: ?>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr>
                <th>Название</th>
                <th>Адрес (slug)</th>
                <th>Порядок</th>
                <th>Товаров</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $category):
                $formId = 'cat-form-' . (int) $category['id'];
            ?>
                <tr>
                    <td><input type="text" form="<?= e($formId) ?>" name="name" value="<?= e($category['name']) ?>" required maxlength="120" data-slug-name></td>
                    <td><input type="text" form="<?= e($formId) ?>" name="slug" value="<?= e($category['slug']) ?>" required maxlength="120" data-slug-target></td>
                    <td><input type="number" form="<?= e($formId) ?>" name="sort_order" value="<?= (int) $category['sort_order'] ?>" step="1" class="input--narrow"></td>
                    <td><span class="badge badge--muted"><?= (int) $category['product_count'] ?></span></td>
                    <td class="table-actions">
                        <!-- <form> не может быть прямым потомком <tr>, поэтому форма редактирования
                             пустая и живёт здесь, а поля выше ссылаются на неё через атрибут form. -->
                        <form id="<?= e($formId) ?>" method="post" action="<?= e(url('/admin/categories/' . $category['id'])) ?>">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="description" value="<?= e($category['description'] ?? '') ?>">
                        </form>
                        <button type="submit" form="<?= e($formId) ?>" class="btn btn--sm btn--secondary">Сохранить</button>
                        <form method="post" action="<?= e(url('/admin/categories/' . $category['id'] . '/delete')) ?>"
                              data-confirm="Удалить категорию «<?= e($category['name']) ?>»? Товары этой категории останутся без категории.">
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
