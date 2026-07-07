<?php
/**
 * Database connection settings.
 *
 * This project runs on PostgreSQL (used by Render's managed database).
 * Render injects a single DATABASE_URL environment variable, so we parse
 * that when present; otherwise we fall back to individual DB_* variables
 * (handy for local Docker/dev use).
 */
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    define('DB_HOST', $parts['host']);
    define('DB_PORT', $parts['port'] ?? 5432);
    define('DB_NAME', ltrim($parts['path'], '/'));
    define('DB_USER', $parts['user']);
    define('DB_PASS', $parts['pass']);
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ?: 5432);
    define('DB_NAME', getenv('DB_NAME') ?: 'siwes_system');
    define('DB_USER', getenv('DB_USER') ?: 'postgres');
    define('DB_PASS', getenv('DB_PASS') ?: '');
}

try {
    $pdo = new PDO(
        'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Please check config/db.php and make sure '
        . 'the "siwes_system" database has been created and schema.sql imported. '
        . 'Error: ' . $e->getMessage());
}
