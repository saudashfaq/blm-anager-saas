<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/GoogleAuth.php';
require_once __DIR__ . '/../../../includes/registration_helper.php';
require_once __DIR__ . '/../../../subscriptions/classes/SubscriptionSessionManager.php';

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

            // Initialize subscription session for existing user
            $subscriptionManager = new SubscriptionSessionManager($pdo);
            $is_superadmin = $_SESSION['is_superadmin'] ?? false;
            $subscriptionData = $subscriptionManager->loadSubscriptionData($user['company_id'], $is_superadmin);

            if (!$subscriptionData) {
                error_log("Warning: Failed to load subscription data for existing Google user: " . $user['id']);
            }

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
                // Initialize subscription session for new user
                $subscriptionManager = new SubscriptionSessionManager($pdo);
                $subscriptionData = $subscriptionManager->handleNewRegistration($result['company_id'], false);

                if (!$subscriptionData) {
                    error_log("Warning: Failed to create subscription session for new Google user: " . $result['user_id']);
                }

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
