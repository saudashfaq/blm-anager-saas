<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/validationHelper.php';
require_once __DIR__ . '/subscriptions/classes/SubscriptionSessionManager.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new ValidationHelper($_POST);
    $response = ['success' => false, 'message' => '', 'redirect' => ''];

    // Validate input using ValidationHelper
    $validator
        ->required('email', 'Email is required')
        ->email('email', 'Please enter a valid email address')
        ->required('password', 'Password is required');

    if ($validator->passes()) {
        try {
            // Get user with company info
            $stmt = $pdo->prepare("
                SELECT 
                    u.*, 
                    c.id as company_id,
                    c.name as company_name,
                    c.status as company_status,
                    u.status as status
                FROM users u
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE u.email = ?
            ");
            $stmt->execute([$_POST['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($_POST['password'], $user['password'])) {
                throw new Exception('Invalid email or password');
            }

            if ($user['status'] !== 'active') {
                throw new Exception('Your account is not active. Please contact support.');
            }

            // Only check company status for non-superadmin users
            if (!$user['is_superadmin'] && (!$user['company_id'] || $user['company_status'] !== 'active')) {
                throw new Exception('Your company account is not active. Please contact support.');
            }

            // Load subscription data - skip for superadmins
            $subscriptionManager = new SubscriptionSessionManager($pdo);
            if ($user['is_superadmin']) {
                $subscriptionData = $subscriptionManager->loadSubscriptionData($user['company_id'], true);
            } else {
                $subscriptionData = $subscriptionManager->loadSubscriptionData($user['company_id'], false);
                if (!$subscriptionData) {
                    throw new Exception('Error loading subscription data. Please try again.');
                }
            }

            // Store user data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status'] = $user['status'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['company_name'] = $user['company_name'];
            $_SESSION['is_superadmin'] = $user['is_superadmin'];

            // Generate CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $response['success'] = true;

            // Check if this is a non-superadmin user on a free plan
            if (!$user['is_superadmin'] && isset($subscriptionData['plan_name']) && $subscriptionData['plan_name'] === PLAN_FREE) {
                // Redirect to subscription page
                $response['redirect'] = 'subscriptions/subscribe.php';
            } else {
                // Set redirect based on role for non-free plan users and superadmins
                $response['redirect'] = $user['is_superadmin'] ? 'super-admin/assign_plan.php' : 'dashboard.php';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
    } else {
        $response['message'] = implode(', ', $validator->getErrors());
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    header('Location: login.php');
    exit;
}
