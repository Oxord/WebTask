<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function verify(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $stored = Session::get(self::SESSION_KEY);
        if (!is_string($stored) || $stored === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }

    public static function field(): string
    {
        $token = self::token();

        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
