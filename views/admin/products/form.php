<?php
/**
 * @var array|null $product
 * @var array $categories
 */

use App\Core\Csrf;

$isEdit = $product !== null;
$action = $isEdit ? '/admin/products/' . $product['id'] : '/admin/products';
?>
<div class="page-head">
    <h1><?= $isEdit ? 'Редактирование товара' : 'Новый товар' ?></h1>
    <a class="btn btn--ghost" href="<?= e(url('/admin/products')) ?>">← К списку товаров</a>
</div>

<form class="form form--panel" method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" data-slug-source>
    <?= Csrf::field() ?>

    <div class="form-grid">
        <div class="form-field">
            <label for="name">Название *</label>
            <input type="text" id="name" name="name" required maxlength="200"
                   value="<?= e(old('name', $product['name'] ?? '')) ?>" data-slug-name>
        </div>

        <div class="form-field">
            <label for="slug">Адрес (slug)</label>
            <input type="text" id="slug" name="slug" maxlength="200"
                   value="<?= e(old('slug', $product['slug'] ?? '')) ?>" data-slug-target>
            <p class="form-hint">Формируется автоматически из названия, можно изменить вручную.</p>
        </div>

        <div class="form-field">
            <label for="category_id">Категория</label>
            <select id="category_id" name="category_id">
                <option value="">Без категории</option>
                <?php $currentCategory = old('category_id', $product['category_id'] ?? ''); ?>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"<?= (string) $currentCategory === (string) $category['id'] ? ' selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="price">Цена, ₽ *</label>
            <input type="number" id="price" name="price" required min="0.01" step="0.01"
                   value="<?= e(old('price', $product['price'] ?? '')) ?>">
        </div>

        <div class="form-field">
            <label for="stock">Остаток, шт. *</label>
            <input type="number" id="stock" name="stock" required min="0" step="1"
                   value="<?= e(old('stock', $product['stock'] ?? 0)) ?>">
        </div>

        <div class="form-field form-field--wide">
            <label for="description">Описание</label>
            <textarea id="description" name="description" rows="5"><?= e(old('description', $product['description'] ?? '')) ?></textarea>
        </div>

        <div class="form-field">
            <label for="image">Изображение</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" data-image-preview="imagePreview">
            <p class="form-hint">JPEG, PNG или WebP, до 5 МБ.</p>
            <div class="image-preview" id="imagePreview">
                <?php if (!empty($product['image_path'])): ?>
                    <img src="<?= e(url($product['image_path'])) ?>" alt="">
                <?php endif; ?>
            </div>
        </div>

        <div class="form-field form-field--checkboxes">
            <label class="checkbox">
                <input type="checkbox" name="is_active" value="1"<?= ((int) old('is_active', $product['is_active'] ?? 1)) === 1 ? ' checked' : '' ?>>
                Активен (виден в каталоге)
            </label>
            <label class="checkbox">
                <input type="checkbox" name="is_featured" value="1"<?= ((int) old('is_featured', $product['is_featured'] ?? 0)) === 1 ? ' checked' : '' ?>>
                Рекомендуемый (попадает на главную)
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Сохранить изменения' : 'Создать товар' ?></button>
        <a class="btn btn--ghost" href="<?= e(url('/admin/products')) ?>">Отмена</a>
    </div>
</form>
