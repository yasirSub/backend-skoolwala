<?php
// Test environment variables
header('Content-Type: application/json');

$env_vars = [
    'DATABASE_URL' => getenv('DATABASE_URL'),
    'DB_HOST' => getenv('DB_HOST'),
    'DB_USER' => getenv('DB_USER'),
    'DB_PASS' => getenv('DB_PASS') ? '***HIDDEN***' : 'NOT SET',
    'DB_NAME' => getenv('DB_NAME'),
    'DB_PORT' => getenv('DB_PORT'),
    'BASE_URL' => getenv('BASE_URL'),
    'CI_ENV' => getenv('CI_ENV'),
];

// Try to build DSN
$database_url = getenv('DATABASE_URL');
$dsn = '';
if ($database_url) {
    $url_parts = parse_url($database_url);
    if ($url_parts) {
        $hostname = $url_parts['host'] ?? 'NOT_FOUND';
        $port = $url_parts['port'] ?? 'NOT_FOUND';
        $database = isset($url_parts['path']) ? ltrim($url_parts['path'], '/') : 'NOT_FOUND';
        $dsn = "pgsql:host=$hostname;port=$port;dbname=$database";
    }
} else {
    $hostname = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: 5432;
    $database = getenv('DB_NAME') ?: 'skoolwala';
    $dsn = "pgsql:host=$hostname;port=$port;dbname=$database";
}

echo json_encode([
    'environment_variables' => $env_vars,
    'built_dsn' => $dsn,
    'php_version' => phpversion(),
    'pdo_drivers' => PDO::getAvailableDrivers()
], JSON_PRETTY_PRINT);

