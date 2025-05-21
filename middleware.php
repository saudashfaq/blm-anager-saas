<?php
// Include required files
require_once __DIR__ . "/config/auth.php"; // Authentication functions (if needed)
require_once __DIR__ . "/config/csrf_helper.php"; // CSRF token functions

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Protection for POST, PUT, DELETE, and AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = null;

    // Check for token in POST data
    if (isset($_POST['csrf_token'])) {
        $csrf_token = $_POST['csrf_token'];
    }
    // Check for token in JSON data
    else {
        $json_input = file_get_contents('php://input');
        if ($json_input) {
            $data = json_decode($json_input, true);
            if (isset($data['csrf_token'])) {
                $csrf_token = $data['csrf_token'];
            }
        }
    }

    // Check for token in headers
    if (!$csrf_token && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    if (empty($csrf_token) || !verifyCSRFToken($csrf_token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            "success" => false,
            "message" => "Forbidden: Invalid CSRF token",
            "debug" => [
                "token_received" => $csrf_token ?? 'none',
                "token_expected" => $_SESSION['csrf_token'] ?? 'none'
            ]
        ]);
        exit;
    }
}

// Generate new CSRF token if not exists
$csrf_token = generateCSRFToken();

// Additional security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-CSRF-TOKEN: " . $csrf_token);
