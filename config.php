<?php
declare(strict_types=1);

$defaults = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'twae_kya_ml',
    'user' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];

$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $defaults = array_merge($defaults, $local);
    }
}

return $defaults;
