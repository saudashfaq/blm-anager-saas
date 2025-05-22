<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/GoogleAuth.php';

$googleAuth = new GoogleAuth();
$userData = $googleAuth->handleCallback($_GET['code']);

if ($userData) {
    try {
        $pdo->beginTransaction();

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, company_id FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        $user = $stmt->fetch();

        if ($user) {
            // User exists - log them in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['company_id'] = $user['company_id'];
            $redirect = BASE_URL . "dashboard.php";
        } else {
            // Create new company
            $stmt = $pdo->prepare("INSERT INTO companies (name, email, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$userData['name'] . "'s Company", $userData['email']]);
            $company_id = $pdo->lastInsertId();

            // Create new user
            $stmt = $pdo->prepare("INSERT INTO users (company_id, username, email, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
            $stmt->execute([
                $company_id,
                explode('@', $userData['email'])[0], // Use email prefix as username
                $userData['email']
            ]);
            $user_id = $pdo->lastInsertId();

            $_SESSION['user_id'] = $user_id;
            $_SESSION['company_id'] = $company_id;
            $redirect = BASE_URL . "dashboard.php";
        }

        $pdo->commit();
        header("Location: " . $redirect);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Google Auth DB Error: " . $e->getMessage());
        header("Location: " . BASE_URL . "login.php?error=Authentication failed");
        exit;
    }
} else {
    header("Location: " . BASE_URL . "login.php?error=Google authentication failed");
    exit;
}
