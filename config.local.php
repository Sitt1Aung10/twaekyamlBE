<?php
declare(strict_types=1);

// Copy to config.local.php to override backend/config.php.
// config.local.php is gitignored. Do not put a real password in this example.
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'twae_kya_ml',
    'user' => 'twae_user',
    'password' => 'MyStrongPass123!',
];
