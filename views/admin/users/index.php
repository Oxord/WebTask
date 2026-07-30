<?php
/**
 * @var array $items
 * @var int $total
 * @var int $pages
 * @var int $page
 * @var string $search
 * @var string $role
 * @var array $roles
 * @var \App\Core\Auth $auth
 */

use App\Core\Csrf;
use App\Core\View;

$roleLabels = ['admin' => 'Администратор', 'moderator' => 'Модератор', 'user' => 'Покупатель'];
$currentUserId = $auth->id();
?>
<div class="page-head">
    <h1>Пользователи</h1>
    <p class="page-head__subtitle">Всего: <?= (int) $total ?></p>
    <a class="btn btn--primary" href="<?= e(url('/admin/users/create')) ?>">+ Новый пользователь</a>
</div>

<form class="filters" method="get" action="<?= e(url('/admin/users')) ?>">
    <div class="filters__field">
        <label for="q">Поиск</label>
        <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Имя или email">
    </div>
    <div class="filters__field">
        <label for="role">Роль</label>
        <select id="role" name="role" data-autosubmit>
            <option value="">Все роли</option>
            <?php foreach ($roles as $value): ?>
                <option value="<?= e($value) ?>"<?= $role === $value ? ' selected' : '' ?>><?= e($roleLabels[$value] ?? $value) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn--secondary">Применить</button>
</form>

<?php if ($items === []): ?>
    <p class="empty-state">Пользователи не найдены.</p>
<?php else: ?>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr>
                <th>ФИО</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Статус</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $user): ?>
                <tr>
                    <td><a href="<?= e(url('/admin/users/' . $user['id'] . '/edit')) ?>"><?= e($user['full_name']) ?></a></td>
                    <td><?= e($user['email']) ?></td>
                    <td><span class="badge badge--role badge--role-<?= e($user['role']) ?>"><?= e($roleLabels[$user['role']] ?? $user['role']) ?></span></td>
                    <td>
                        <span class="badge badge--<?= (int) $user['is_active'] === 1 ? 'success' : 'muted' ?>">
                            <?= (int) $user['is_active'] === 1 ? 'Активен' : 'Заблокирован' ?>
                        </span>
                    </td>
                    <td class="table-actions">
                        <a class="btn btn--sm btn--secondary" href="<?= e(url('/admin/users/' . $user['id'] . '/edit')) ?>">Изменить</a>
                        <?php if ((int) $user['id'] !== $currentUserId): ?>
                            <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/delete')) ?>" data-confirm="Удалить пользователя «<?= e($user['full_name']) ?>»?">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn btn--sm btn--danger">Удалить</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= View::partial('admin/partials/pagination', [
        'page' => $page,
        'pages' => $pages,
        'basePath' => '/admin/users',
        'query' => array_filter(['q' => $search, 'role' => $role]),
    ]) ?>
<?php endif; ?>
