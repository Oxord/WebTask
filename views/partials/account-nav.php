<?php
/** @var string $active */
$links = [
    'index' => ['/account', 'Профиль'],
    'orders' => ['/account/orders', 'Мои заказы'],
];
?>
<nav class="account-nav" aria-label="Навигация личного кабинета">
    <?php foreach ($links as $key => [$path, $label]): ?>
        <a href="<?= e(url($path)) ?>" class="<?= $active === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <form action="<?= e(url('/logout')) ?>" method="post">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" style="width:100%;text-align:left;background:none;border:none;padding:var(--space-3);border-radius:var(--radius-s);font-weight:500">Выйти</button>
    </form>
</nav>
