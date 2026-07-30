<?php
/**
 * @var array|null $user
 * @var array $roles
 * @var \App\Core\Auth $auth
 */

use App\Core\Csrf;

$isEdit = $user !== null;
$action = $isEdit ? '/admin/users/' . $user['id'] : '/admin/users';
$roleLabels = ['admin' => 'Администратор', 'moderator' => 'Модератор', 'user' => 'Покупатель'];
$isSelf = $isEdit && $auth->id() === (int) $user['id'];
?>
<div class="page-head">
    <h1><?= $isEdit ? 'Редактирование пользователя' : 'Новый пользователь' ?></h1>
    <a class="btn btn--ghost" href="<?= e(url('/admin/users')) ?>">← К списку пользователей</a>
</div>

<form class="form form--panel" method="post" action="<?= e(url($action)) ?>">
    <?= Csrf::field() ?>

    <div class="form-grid">
        <div class="form-field">
            <label for="full_name">ФИО *</label>
            <input type="text" id="full_name" name="full_name" required maxlength="150"
                   value="<?= e(old('full_name', $user['full_name'] ?? '')) ?>">
        </div>

        <div class="form-field">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required maxlength="190"
                   value="<?= e(old('email', $user['email'] ?? '')) ?>">
        </div>

        <div class="form-field">
            <label for="phone">Телефон</label>
            <input type="text" id="phone" name="phone" value="<?= e(old('phone', $user['phone'] ?? '')) ?>">
        </div>

        <div class="form-field form-field--wide">
            <label for="address">Адрес</label>
            <input type="text" id="address" name="address" maxlength="255" value="<?= e(old('address', $user['address'] ?? '')) ?>">
        </div>

        <div class="form-field">
            <label for="role">Роль *</label>
            <select id="role" name="role" required>
                <?php foreach ($roles as $value): ?>
                    <option value="<?= e($value) ?>"<?= (string) old('role', $user['role'] ?? 'user') === $value ? ' selected' : '' ?>>
                        <?= e($roleLabels[$value] ?? $value) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($isSelf): ?>
                <p class="form-hint">Нельзя снять с себя права администратора.</p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="password"><?= $isEdit ? 'Новый пароль' : 'Пароль *' ?></label>
            <input type="password" id="password" name="password" minlength="6" autocomplete="new-password"<?= $isEdit ? '' : ' required' ?>>
            <?php if ($isEdit): ?>
                <p class="form-hint">Оставьте пустым, чтобы не менять текущий пароль.</p>
            <?php endif; ?>
        </div>

        <div class="form-field form-field--checkboxes">
            <label class="checkbox">
                <input type="checkbox" name="is_active" value="1"<?= ((int) old('is_active', $user['is_active'] ?? 1)) === 1 ? ' checked' : '' ?>>
                Активен (может входить в систему)
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Сохранить изменения' : 'Создать пользователя' ?></button>
        <a class="btn btn--ghost" href="<?= e(url('/admin/users')) ?>">Отмена</a>
    </div>
</form>
