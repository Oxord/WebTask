<?php
/**
 * @var \App\Core\Auth|null $auth
 * @var int $cartCount
 * @var string $currentPath
 */

use App\Core\Csrf;

$navItems = [
    ['/about', 'О нас'],
    ['/catalog', 'Каталог'],
    ['/reviews', 'Отзывы'],
    ['/contacts', 'Контакты'],
    ['/promotions', 'Акции'],
];

$isActive = static function (string $path) use ($currentPath): bool {
    if ($path === '/') {
        return $currentPath === '/';
    }

    return $currentPath === $path || str_starts_with($currentPath, $path . '/');
};
?>
<header class="site-header">
    <div class="container site-header__bar">
        <a class="brand" href="<?= e(url('/')) ?>">
            <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="36" height="36">
            <span>Декор для дома</span>
        </a>

        <button type="button" class="burger" id="burgerToggle" aria-expanded="false" aria-controls="mainNav" aria-label="Открыть меню">
            <span class="burger__box"><span></span></span>
        </button>

        <nav class="main-nav" id="mainNav" aria-label="Главное меню">
            <a class="main-nav__link<?= $isActive('/') && $currentPath === '/' ? ' is-active' : '' ?>" href="<?= e(url('/')) ?>">Главная</a>
            <?php foreach ($navItems as [$path, $label]): ?>
                <a class="main-nav__link<?= $isActive($path) ? ' is-active' : '' ?>" href="<?= e(url($path)) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <a class="main-nav__link<?= $isActive('/cart') ? ' is-active' : '' ?>" href="<?= e(url('/cart')) ?>">
                Корзина
                <?php if ($cartCount > 0): ?>
                    <span class="main-nav__count" id="cartCount"><?= (int) $cartCount ?></span>
                <?php endif; ?>
            </a>
            <?php if ($auth?->check()): ?>
                <a class="main-nav__link<?= $isActive('/account') ? ' is-active' : '' ?>" href="<?= e(url('/account')) ?>">Личный кабинет</a>
                <?php if ($auth->can('admin.access')): ?>
                    <a class="main-nav__link" href="<?= e(url('/admin')) ?>">Админ-панель</a>
                <?php endif; ?>
                <form action="<?= e(url('/logout')) ?>" method="post" style="display:inline">
                    <?= Csrf::field() ?>
                    <button type="submit" class="main-nav__link" style="background:none;border:none;width:100%;text-align:left">Выйти</button>
                </form>
            <?php else: ?>
                <a class="main-nav__link<?= $isActive('/login') ? ' is-active' : '' ?>" href="<?= e(url('/login')) ?>">Войти</a>
                <a class="main-nav__link<?= $isActive('/register') ? ' is-active' : '' ?>" href="<?= e(url('/register')) ?>">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
