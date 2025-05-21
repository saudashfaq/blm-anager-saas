<?php

session_start();
require_once __DIR__ . '/config/auth.php';

// Check if BASE_URL is defined, if not redirect to installation page
if (!defined('BASE_URL')) {
    header("Location: install/index.php");
    exit;
}


if (isset($_SESSION['user_id'])) {
    // Check if user is a superadmin
    if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === 1) {
        header("Location:" . BASE_URL . "super-admin/subscribers.php");
        exit;
    }

    // For regular users, check subscription status
    if (
        isset($_SESSION['subscription']) &&
        isset($_SESSION['subscription']['plan_name']) &&
        $_SESSION['subscription']['plan_name'] === PLAN_FREE
    ) {
        // Free plan users are redirected to subscription page
        header("Location:" . BASE_URL . "subscriptions/subscribe.php");
        exit;
    }

    // All other users go to dashboard
    header("Location:" . BASE_URL . "dashboard.php");
    exit;
}
