<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private static string $basePath = __DIR__ . '/../../views';

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    public static function render(string $template, array $data = [], ?string $layout = 'main'): string
    {
        $content = self::renderFile(self::templatePath($template), $data);

        if ($layout === null) {
            return $content;
        }

        $layoutData = $data;
        $layoutData['content'] = $content;

        return self::renderFile(self::$basePath . '/layouts/' . $layout . '.php', $layoutData);
    }

    public static function partial(string $name, array $data = []): string
    {
        return self::renderFile(self::templatePath($name), $data);
    }

    private static function templatePath(string $template): string
    {
        return self::$basePath . '/' . ltrim($template, '/') . '.php';
    }

    private static function renderFile(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;

        return (string) ob_get_clean();
    }
}
