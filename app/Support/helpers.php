<?php
declare(strict_types=1);

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $cfg = null;
        if ($cfg === null) {
            $cfg = require BASE_PATH . '/config/config.php';
        }
        $value = $cfg;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string)config('app.url', ''), '/');
        $path = ltrim($path, '/');
        return $path === '' ? $base : $base . '/' . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('image_src')) {
    function image_src(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return asset('img/placeholder.svg');
        }
        // Make the value safe inside an HTML attribute
        $safe = str_replace(['"', "'", '<', '>', ' '], ['%22', '%27', '%3C', '%3E', '%20'], trim($value));
        if (preg_match('#^https?://#i', $safe)) {
            return $safe;
        }
        return url($safe);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        $target = preg_match('#^https?://#i', $path) ? $path : url($path);
        header('Location: ' . $target, true, 302);
        exit;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value = null): mixed
    {
        if (func_num_args() >= 2) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $v = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $v;
    }
}

if (!function_exists('view')) {
    function view(string $path, array $data = []): string
    {
        return \App\Core\View::render($path, $data);
    }
}