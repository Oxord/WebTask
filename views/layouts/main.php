<?php
/**
 * Общий layout публичной части. Auth и счётчик корзины готовит контроллер
 * (см. App\Controller\RendersPage) и передаёт сюда через View::render().
 * Layout сам в БД не ходит — это критично для страницы ошибки: её рендерит
 * public/index.php напрямую, без контроллера, и $auth/$cartCount там не заданы.
 *
 * @var string $content
 * @var \App\Core\Auth|null $auth
 * @var int|null $cartCount
 */

use App\Core\Session;
use App\Core\View;

$auth ??= null;
$cartCount ??= 0;
$currentPath = rtrim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), '/');
if ($currentPath === '') {
    $currentPath = '/';
}
$flashes = Session::takeFlashes();
$title = isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' — Декор для дома' : 'Декор для дома — магазин уюта для интерьера';
$description = $metaDescription ?? 'Интернет-магазин декора для дома: текстиль, свет, керамика и ароматы с доставкой по России.';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main">Перейти к содержимому</a>

<?= View::partial('partials/header', ['auth' => $auth, 'cartCount' => $cartCount, 'currentPath' => $currentPath]) ?>

<?= View::partial('partials/flashes', ['flashes' => $flashes]) ?>

<main id="main">
<?= $content ?>
</main>

<?= View::partial('partials/footer') ?>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
