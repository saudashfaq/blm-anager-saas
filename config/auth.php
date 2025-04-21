<?php

require_once __DIR__ . '/session_manager.php';
require_once __DIR__ . '/config.php';

if (!defined('BASE_URL')) {
    $installPath = null;

    // Try install folder in current directory
    if (is_dir(__DIR__ . '/install')) {
        $installPath = 'install/index.php';
    }
    // Try install folder in parent directory
    elseif (is_dir(dirname(__DIR__) . '/install')) {
        $dirName = basename(dirname(__DIR__));
        $installPath = $dirName . '/install/index.php';
    }

    if ($installPath) {
        // Build full URL to redirect
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

        // Always redirect to correct install folder path
        $redirectUrl = $protocol . $host . '/' . trim($installPath, '/');
        header("Location: $redirectUrl");
        exit;
    } else {
        echo "Install folder not found.";
        exit;
    }
}

$request_uri = filter_var(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), FILTER_SANITIZE_URL);

if (preg_match("/login\.php$/", $request_uri)) {
    if (!empty($_SESSION['user_id'])) {
        header("Location:" . BASE_URL . "dashboard.php");
        exit;
    }
} else {
    //some other URL
    if (empty($_SESSION['user_id'])) {
        header("Location:" . BASE_URL . "login.php");
        exit;
    }

    // Check if company is active
    if (!empty($_SESSION['company_id'])) {
        require_once __DIR__ . '/db.php';
        try {
            $stmt = $pdo->prepare("SELECT status FROM companies WHERE id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            $company = $stmt->fetch();

            if (!$company || $company['status'] !== 'active') {
                session_destroy();
                header("Location:" . BASE_URL . "login.php?error=Company account is not active");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Company status check error: " . $e->getMessage());
        }
    }
}
