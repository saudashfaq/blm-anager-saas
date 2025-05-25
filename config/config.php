<?php
// Load environment variables if .env file exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    try {
        $envVars = parse_ini_file($envFile, false, INI_SCANNER_RAW);
        if ($envVars === false) {
            error_log('Error parsing .env file');
        } else {
            foreach ($envVars as $key => $value) {
                $value = trim($value);
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    } catch (Exception $e) {
        error_log('Error loading .env file: ' . $e->getMessage());
    }
}

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root');
define('DB_NAME', getenv('DB_NAME') ?: 'backlinks_manager_saas');

// Application URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseDir = rtrim($scriptDir, '/\\');

// Get SITE_URL from environment or construct it
$siteUrl = getenv('SITE_URL');
if (empty($siteUrl)) {
    $siteUrl = $protocol . $host . $baseDir;
}

// Normalize URLs
$siteUrl = rtrim($siteUrl, '/');
define('SITE_URL', $siteUrl);
define('BASE_URL', $siteUrl . '/');

// Function to generate application URLs
function url($path = '')
{
    $path = ltrim($path, '/');
    return BASE_URL . $path;
}

// Error Reporting
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
