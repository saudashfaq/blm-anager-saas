<?php
// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $envVars = parse_ini_file(__DIR__ . '/../.env');
    foreach ($envVars as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root');
define('DB_NAME', getenv('DB_NAME') ?: 'backlinks_manager_saas');

// Application URLs
define('SITE_URL', rtrim(getenv('SITE_URL') ?: 'http://localhost:8888/backlinks_manager_saas', '/'));
define('BASE_URL', rtrim(getenv('BASE_URL') ?: SITE_URL . '/', '/') . '/');

// Error Reporting
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
