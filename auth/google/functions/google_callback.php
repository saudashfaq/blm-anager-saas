<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/GoogleAuth.php';
require_once __DIR__ . '/../../../includes/registration_helper.php';

$googleAuth = new GoogleAuth();
$userData = $googleAuth->handleCallback($_GET['code']);

if ($userData) {
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, company_id FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        $user = $stmt->fetch();

        if ($user) {
            // User exists - initialize their session
            initializeUserSession($user['id'], $user['company_id']);
            $redirect = BASE_URL . "dashboard.php";
        } else {
            // Prepare user data for registration
            $registrationData = [
                'company_name' => $userData['name'] . "'s Company",
                'company_email' => $userData['email'],
                'username' => explode('@', $userData['email'])[0],
                'email' => $userData['email']
            ];

            // Register the new user - session will be initialized in registerUser()
            $result = registerUser($registrationData);

            if ($result['success']) {
                $redirect = BASE_URL . "dashboard.php";
            } else {
                throw new Exception($result['error']);
            }
        }

        header("Location: " . $redirect);
        exit;
    } catch (Exception $e) {
        error_log("Google Auth Error: " . $e->getMessage());
        header("Location: " . BASE_URL . "login.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: " . BASE_URL . "login.php?error=Google authentication failed");
    exit;
}
