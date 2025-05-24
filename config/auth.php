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

// List of public pages that don't require authentication
$public_pages = [
    'login.php',
    'index.php',
    'register.php',
    'privacy-policy.php',
    'terms-of-service.php'
];

// Check if current page is in public pages list
$is_public_page = false;

// First check if it's the root URL (including project directory)
if (
    $request_uri === '/' ||
    $request_uri === rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ||
    $request_uri === '/backlinks_manager_saas/' ||
    $request_uri === rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/'
) {
    $is_public_page = true;
} else {
    // Then check for specific public pages
    foreach ($public_pages as $page) {
        if (preg_match("/{$page}$/", $request_uri)) {
            $is_public_page = true;
            break;
        }
    }
}

if (preg_match("/login\.php$/", $request_uri)) {
    if (!empty($_SESSION['user_id'])) {
        header("Location:" . BASE_URL . "dashboard.php");
        exit;
    }
} elseif (!$is_public_page) {
    //some other URL that requires authentication
    if (empty($_SESSION['user_id'])) {
        header("Location:" . BASE_URL . "login.php");
        exit;
    }

    // Check if company is active
    if (!empty($_SESSION['company_id']) && empty($_SESSION['is_superadmin'])) {
        require_once __DIR__ . '/db.php';
        try {
            $stmt = $pdo->prepare("SELECT status FROM companies WHERE id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            $company = $stmt->fetch();

            if (!$company || !in_array($company['status'], ['active'])) {
                // Log the event
                error_log("Company {$_SESSION['company_id']} status check failed: " . ($company ? $company['status'] : 'not found'));

                // Store the error message before destroying session
                $error_message = $company ?
                    "Company account is not active or has been suspended" :
                    "Company account not found";

                // Clear all session data
                session_unset();
                session_destroy();

                // Start a new session for the error message
                session_start();
                $_SESSION['error'] = $error_message;

                header("Location:" . BASE_URL . "login.php?error=" . urlencode($error_message));
                exit;
            }
        } catch (PDOException $e) {
            error_log("Company status check error: " . $e->getMessage());
            // On database error, err on the side of caution and log out the user
            session_destroy();
            session_start();
            header("Location:" . BASE_URL . "login.php?error=" . urlencode("System error occurred. Please try again."));
            exit;
        }
    }
}
