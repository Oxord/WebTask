<?php

declare(strict_types=1);

/**
 * Роутер для встроенного сервера PHP (`php -S ... scripts/router.php`).
 *
 * Встроенный сервер не читает public/.htaccess, поэтому здесь воспроизведены те же
 * правила: служебные файлы закрыты, существующая статика отдаётся как есть,
 * всё остальное уходит во фронт-контроллер.
 *
 * Используется только в нативном режиме (scripts/serve-native.sh). Под Docker
 * запросы обрабатывает Apache по правилам из .htaccess.
 */

$publicDir = realpath(__DIR__ . '/../public');
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$path = rawurldecode(is_string($path) && $path !== '' ? $path : '/');

// Аналог правил запрета из .htaccess: точечные файлы (.env, .git) и служебные конфиги.
if (preg_match('#(^|/)\.#', $path) === 1
    || preg_match('#^/(composer\.(json|lock)|phpunit\.xml)$#', $path) === 1
) {
    http_response_code(403);
    echo 'Forbidden';

    return true;
}

// Существующий файл внутри public/ отдаёт сам сервер (return false).
// realpath + проверка префикса — защита от выхода за пределы каталога.
if ($path !== '/' && $publicDir !== false) {
    $file = realpath($publicDir . $path);
    if ($file !== false && is_file($file) && str_starts_with($file, $publicDir . DIRECTORY_SEPARATOR)) {
        return false;
    }
}

require $publicDir . '/index.php';
