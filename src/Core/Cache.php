<?php

declare(strict_types=1);

namespace App\Core;

final class Cache
{
    private static string $dir = __DIR__ . '/../../var/cache';

    public static function setDirectory(string $dir): void
    {
        self::$dir = rtrim($dir, '/');
    }

    public static function remember(string $key, int $ttl, callable $producer): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $producer();
        self::put($key, $value, $ttl);

        return $value;
    }

    public static function get(string $key): mixed
    {
        $file = self::path($key);
        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }

        $payload = @unserialize($raw);
        if (!is_array($payload) || !isset($payload['expires'], $payload['value'])) {
            return null;
        }

        if ($payload['expires'] !== 0 && $payload['expires'] < time()) {
            @unlink($file);

            return null;
        }

        return $payload['value'];
    }

    public static function put(string $key, mixed $value, int $ttl): void
    {
        if (!self::ensureDir()) {
            return;
        }

        $payload = serialize([
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'value' => $value,
        ]);

        @file_put_contents(self::path($key), $payload);
    }

    public static function forget(string $key): void
    {
        $file = self::path($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public static function flush(): void
    {
        if (!is_dir(self::$dir)) {
            return;
        }

        foreach (glob(self::$dir . '/*.cache') ?: [] as $file) {
            @unlink($file);
        }
    }

    private static function ensureDir(): bool
    {
        if (!is_dir(self::$dir)) {
            @mkdir(self::$dir, 0775, true);
        }

        return is_dir(self::$dir) && is_writable(self::$dir);
    }

    private static function path(string $key): string
    {
        return self::$dir . '/' . hash('sha256', $key) . '.cache';
    }
}
