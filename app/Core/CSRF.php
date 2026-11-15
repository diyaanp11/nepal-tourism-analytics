<?php
declare(strict_types=1);

namespace App\Core;

final class CSRF
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    public static function verify(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function checkOrAbort(?string $token): void
    {
        if (!self::verify($token)) {
            http_response_code(419);
            exit('CSRF token mismatch.');
        }
    }
}