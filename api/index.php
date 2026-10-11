<?php

// Set up writable /tmp directories for Vercel serverless runtime
$tmpStorage = '/tmp/storage';
$dirs = [
    $tmpStorage . '/app/public',
    $tmpStorage . '/framework/cache/data',
    $tmpStorage . '/framework/sessions',
    $tmpStorage . '/framework/testing',
    $tmpStorage . '/framework/views',
    $tmpStorage . '/logs',
];

foreach ($dirs as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// If DB_HOST is configured in Vercel (e.g., TiDB Cloud), use MySQL with SSL CA bundle; otherwise fallback to /tmp/database.sqlite
$dbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? ''));
if ($dbHost !== '' && $dbHost !== '127.0.0.1' && $dbHost !== 'localhost') {
    if (! getenv('DB_CONNECTION')) {
        putenv('DB_CONNECTION=mysql');
        $_ENV['DB_CONNECTION'] = 'mysql';
        $_SERVER['DB_CONNECTION'] = 'mysql';
    }

    if (! getenv('MYSQL_ATTR_SSL_CA')) {
        foreach (['/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/certs/ca-certificates.crt', '/etc/ssl/cert.pem'] as $caPath) {
            if (file_exists($caPath)) {
                putenv("MYSQL_ATTR_SSL_CA={$caPath}");
                $_ENV['MYSQL_ATTR_SSL_CA'] = $caPath;
                $_SERVER['MYSQL_ATTR_SSL_CA'] = $caPath;
                break;
            }
        }
    }
} else {
    putenv('DB_CONNECTION=sqlite');
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_SERVER['DB_CONNECTION'] = 'sqlite';

    $dbPath = '/tmp/database.sqlite';
    putenv("DB_DATABASE={$dbPath}");
    $_ENV['DB_DATABASE'] = $dbPath;
    $_SERVER['DB_DATABASE'] = $dbPath;

    if (! file_exists($dbPath)) {
        @touch($dbPath);
    }
}

require __DIR__ . '/../public/index.php';
