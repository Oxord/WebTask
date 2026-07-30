<?php
/**
 * @var array|null $promotion
 * @var array $allProducts
 * @var int[] $selectedIds
 */

use App\Core\Csrf;

$isEdit = $promotion !== null;
$action = $isEdit ? '/admin/promotions/' . $promotion['id'] : '/admin/promotions';
$selected = old('product_ids', $selectedIds);
$selected = is_array($selected) ? array_map('intval', $selected) : [];

$toDatetimeLocal = static function (?string $value): string {
    if (!$value) {
        return '';
    }

    return date('Y-m-d\TH:i', strtotime($value));
};
?>
<div class="page-head">
    <h1><?= $isEdit ? 'Редактирование акции' : 'Новая акция' ?></h1>
    <a class="btn btn--ghost" href="<?= e(url('/admin/promotions')) ?>">← К списку акций</a>
</div>

<form class="form form--panel" method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" data-slug-source>
    <?= Csrf::field() ?>

    <div class="form-grid">
        <div class="form-field">
            <label for="title">Заголовок *</label>
            <input type="text" id="title" name="title" required maxlength="200"
                   value="<?= e(old('title', $promotion['title'] ?? '')) ?>" data-slug-name>
        </div>

        <div class="form-field">
            <label for="slug">Адрес (slug)</label>
            <input type="text" id="slug" name="slug" maxlength="200"
                   value="<?= e(old('slug', $promotion['slug'] ?? '')) ?>" data-slug-target>
        </div>

        <div class="form-field">
            <label for="discount_percent">Скидка, % *</label>
            <input type="number" id="discount_percent" name="discount_percent" required min="1" max="99" step="1"
                   value="<?= e(old('discount_percent', $promotion['discount_percent'] ?? 10)) ?>">
        </div>

        <div class="form-field">
            <label for="starts_at">Начало действия *</label>
            <input type="datetime-local" id="starts_at" name="starts_at" required
                   value="<?= e(old('starts_at', $toDatetimeLocal($promotion['starts_at'] ?? null))) ?>">
        </div>

        <div class="form-field">
            <label for="ends_at">Окончание действия *</label>
            <input type="datetime-local" id="ends_at" name="ends_at" required
                   value="<?= e(old('ends_at', $toDatetimeLocal($promotion['ends_at'] ?? null))) ?>">
        </div>

        <div class="form-field form-field--wide">
            <label for="description">Описание</label>
            <textarea id="description" name="description" rows="4"><?= e(old('description', $promotion['description'] ?? '')) ?></textarea>
        </div>

        <div class="form-field">
            <label for="image">Изображение</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" data-image-preview="promoImagePreview">
            <p class="form-hint">JPEG, PNG или WebP, до 5 МБ.</p>
            <div class="image-preview" id="promoImagePreview">
                <?php if (!empty($promotion['image_path'])): ?>
                    <img src="<?= e(url($promotion['image_path'])) ?>" alt="">
                <?php endif; ?>
            </div>
        </div>

        <div class="form-field form-field--checkboxes">
            <label class="checkbox">
                <input type="checkbox" name="is_active" value="1"<?= ((int) old('is_active', $promotion['is_active'] ?? 1)) === 1 ? ' checked' : '' ?>>
                Активна
            </label>
        </div>

        <div class="form-field form-field--wide">
            <label for="product_ids">Товары-участники</label>
            <select id="product_ids" name="product_ids[]" multiple size="10" class="select--multi">
                <?php foreach ($allProducts as $product): ?>
                    <option value="<?= (int) $product['id'] ?>"<?= in_array((int) $product['id'], $selected, true) ? ' selected' : '' ?>>
                        <?= e($product['name']) ?> (<?= e(price((float) $product['price'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="form-hint">Удерживайте Ctrl (Cmd на Mac), чтобы выбрать несколько товаров.</p>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Сохранить изменения' : 'Создать акцию' ?></button>
        <a class="btn btn--ghost" href="<?= e(url('/admin/promotions')) ?>">Отмена</a>
    </div>
</form>
