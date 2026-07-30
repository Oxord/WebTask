<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $items = [];

    public static function load(string $envPath): void
    {
        self::$items = [];

        if (!is_file($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), "\"'");
            self::$items[$key] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $env = getenv($key);
        if ($env !== false) {
            return $env;
        }

        return self::$items[$key] ?? $default;
    }

    public static function isDebug(): bool
    {
        return filter_var(self::get('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function isProd(): bool
    {
        return self::get('APP_ENV', 'dev') === 'prod';
    }
}
