<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/validationHelper.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$validator = new ValidationHelper($_POST);

$validator
    ->required('email', 'Email is required')
    ->email('email', 'Please enter a valid email address')
    ->required('password', 'Password is required');

if ($validator->passes()) {
    try {
        $stmt = $pdo->prepare("SELECT u.*, c.name as company_name FROM users u LEFT JOIN companies c ON u.company_id = c.id WHERE u.email = ?");
        $stmt->execute([$_POST['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['company_name'] = $user['company_name'];
            $_SESSION['is_superadmin'] = $user['is_superadmin'];

            // Redirect based on role
            if ($user['is_superadmin']) {
                header('Location: superadmin/dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            header('Location: login.php?error=Invalid email or password');
            exit;
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        header('Location: login.php?error=Login failed. Please try again.');
        exit;
    }
} else {
    $errors = $validator->getErrors();
    $errorMessage = implode(', ', $errors);
    header('Location: login.php?error=' . urlencode($errorMessage));
    exit;
}
