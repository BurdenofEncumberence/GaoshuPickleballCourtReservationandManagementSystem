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

// Ensure SQLite database file exists in /tmp when using sqlite on Vercel
if ((getenv('DB_CONNECTION') ?: 'sqlite') === 'sqlite') {
    $dbPath = getenv('DB_DATABASE') ?: '/tmp/database.sqlite';
    if (! file_exists($dbPath)) {
        @touch($dbPath);
    }
}

require __DIR__ . '/../public/index.php';
