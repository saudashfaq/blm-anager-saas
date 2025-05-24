<?php
require_once __DIR__ . '/../middleware.php';

// Only allow superadmins to access this script
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=' . urlencode('Access denied. Superadmin privileges required.'));
    exit;
}
