<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Session;

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function price(float $value): string
{
    $decimals = abs($value - round($value)) > 0.0001 ? 2 : 0;
    $formatted = number_format($value, $decimals, ',', "\u{00A0}");

    return $formatted . "\u{00A0}₽";
}

function url(string $path): string
{
    $base = rtrim((string) Config::get('APP_URL', ''), '/');
    $path = '/' . ltrim($path, '/');

    return $base . $path;
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function old(string $key, $default = '')
{
    static $input = null;
    if ($input === null) {
        $input = Session::takeOldInput();
    }

    return $input[$key] ?? $default;
}

// Простая побуквенная транслитерация — достаточно для ЧПУ-ссылок магазина.
function slugify(string $text): string
{
    $map = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
        'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];

    $text = mb_strtolower($text, 'UTF-8');
    $transliterated = strtr($text, $map);
    $transliterated = (string) preg_replace('/[^a-z0-9]+/u', '-', $transliterated);
    $transliterated = trim($transliterated, '-');

    if ($transliterated === '') {
        return 'n-' . bin2hex(random_bytes(4));
    }

    return $transliterated;
}
