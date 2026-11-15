<?php
declare(strict_types=1);

$defaults = [
    'app' => [
        'name'     => 'Nepal Tourism Plus',
        'env'      => 'development',
        'debug'    => true,
        'url'      => 'http://localhost/nepal-tourism',
        'timezone' => 'Asia/Kathmandu',
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'nepal_tourism',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name'     => 'nepal_tourism_session',
        'lifetime' => 7200,
    ],
    'uploads' => [
        'path'        => dirname(__DIR__) . '/storage/imports',
        'max_size'    => 10 * 1024 * 1024,
        'allowed_ext' => ['csv'],
    ],
    'pagination' => [
        'per_page' => 20,
    ],
];

$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $defaults = array_replace_recursive($defaults, $local);
    }
}

return $defaults;