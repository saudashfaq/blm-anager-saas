<?php
require_once __DIR__ . '/../middleware.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/company_helper.php';

// Ensure the user is an admin
if ($_SESSION['role'] !== 'admin') {
    header("Location:" . BASE_URL . "index.php");
    exit;
}

// Check if ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No user ID provided";
    header("Location:" . BASE_URL . "users/index.php");
    exit;
}

$userId = (int)$_GET['id'];
$company_id = get_current_company_id();

try {
    // Check if the user exists and belongs to the current company
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ? AND company_id = ?");
    $stmt->execute([$userId, $company_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found";
        header("Location:" . BASE_URL . "users/index.php");
        exit;
    }

    // Delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND company_id = ?");
    $result = $stmt->execute([$userId, $company_id]);

    if ($result) {
        $_SESSION['success'] = "User '{$user['username']}' has been deleted successfully";
    } else {
        $_SESSION['error'] = "Failed to delete user";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Redirect back to the users index page
header("Location:" . BASE_URL . "users/index.php");
exit;
