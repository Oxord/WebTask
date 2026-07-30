<?php
/**
 * @var string $content
 * @var \App\Core\Auth $auth
 * @var array|null $currentUser
 */

use App\Core\Csrf;
use App\Core\Session;

$currentPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

$isActive = static function (string $path) use ($currentPath): bool {
    if ($path === '/admin') {
        return $currentPath === '/admin' || $currentPath === '/admin/';
    }

    return $currentPath === $path || str_starts_with($currentPath, $path . '/');
};

$navItems = [
    ['/admin', 'Дашборд', 'admin.access'],
    ['/admin/products', 'Товары', 'products.manage'],
    ['/admin/categories', 'Категории', 'categories.manage'],
    ['/admin/promotions', 'Акции', 'promotions.manage'],
    ['/admin/reviews', 'Отзывы', 'reviews.moderate'],
    ['/admin/orders', 'Заказы', 'orders.view'],
    ['/admin/users', 'Пользователи', 'users.manage'],
    ['/admin/messages', 'Сообщения', 'admin.access'],
];

$roleLabels = ['admin' => 'Администратор', 'moderator' => 'Модератор', 'user' => 'Покупатель'];
$roleLabel = $roleLabels[$auth->role() ?? ''] ?? ($auth->role() ?? '');

$flashes = Session::takeFlashes();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Админ-панель — Декор для дома</title>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">
<div class="admin-shell" id="adminShell">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar__brand">
            <span class="admin-sidebar__logo">ДД</span>
            <span>Декор для дома</span>
        </div>

        <nav class="admin-nav" aria-label="Навигация по админ-панели">
            <?php foreach ($navItems as [$path, $label, $perm]): ?>
                <?php if ($auth->can($perm)): ?>
                    <a class="admin-nav__link<?= $isActive($path) ? ' is-active' : '' ?>" href="<?= e(url($path)) ?>">
                        <?= e($label) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar__footer">
            <a href="<?= e(url('/')) ?>" class="admin-nav__link admin-nav__link--muted">← На сайт</a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="admin-topbar__burger" id="adminSidebarToggle" aria-expanded="false" aria-controls="adminSidebar" aria-label="Открыть меню">
                <span></span><span></span><span></span>
            </button>

            <div class="admin-topbar__spacer"></div>

            <div class="admin-topbar__user">
                <div class="admin-topbar__user-info">
                    <span class="admin-topbar__name"><?= e($currentUser['full_name'] ?? 'Пользователь') ?></span>
                    <span class="badge badge--role badge--role-<?= e($auth->role() ?? 'user') ?>"><?= e($roleLabel) ?></span>
                </div>
                <form action="<?= e(url('/logout')) ?>" method="post" class="admin-topbar__logout">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn--ghost btn--sm">Выйти</button>
                </form>
            </div>
        </header>

        <?php if ($flashes !== []): ?>
            <div class="admin-flashes">
                <?php foreach ($flashes as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert--<?= e($type) ?>"><?= e($message) ?></div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <main class="admin-content">
            <?= $content ?>
        </main>
    </div>
</div>

<div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
