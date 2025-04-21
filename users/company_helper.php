<?php

/**
 * Ensures that the current user has access to the specified company's data
 * @param int $company_id The company ID to check against
 * @return bool True if access is allowed, false otherwise
 */
function verify_company_access($company_id)
{
    if (!isset($_SESSION['company_id']) || $_SESSION['company_id'] != $company_id) {
        return false;
    }
    return true;
}

/**
 * Adds company_id condition to SQL queries
 * @param string $sql The SQL query to modify
 * @param array $params The parameters array to modify
 * @return array Modified SQL and parameters
 */
function add_company_condition($sql, $params = [])
{
    if (!isset($_SESSION['company_id'])) {
        throw new Exception('Company ID not found in session');
    }

    // Check if WHERE clause already exists
    if (stripos($sql, 'WHERE') !== false) {
        $sql = preg_replace('/WHERE/i', 'WHERE company_id = ? AND', $sql, 1);
    } else {
        $sql .= ' WHERE company_id = ?';
    }

    array_unshift($params, $_SESSION['company_id']);
    return [$sql, $params];
}

/**
 * Gets the current user's company ID
 * @return int|null The company ID or null if not set
 */
function get_current_company_id()
{
    return $_SESSION['company_id'] ?? null;
}

/**
 * Checks if the current user is a company admin
 * @return bool True if user is a company admin, false otherwise
 */
function is_company_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Gets all users for the current company
 * @param PDO $pdo Database connection
 * @return array Array of users
 */
function get_company_users($pdo)
{
    $company_id = get_current_company_id();
    if (!$company_id) {
        return [];
    }

    $stmt = $pdo->prepare("SELECT id, username, email, role, created_at FROM users WHERE company_id = ?");
    $stmt->execute([$company_id]);
    return $stmt->fetchAll();
}

/**
 * Gets company subscription details
 * @param PDO $pdo Database connection
 * @return array|null Company subscription details or null if not found
 */
function get_company_subscription($pdo)
{
    $company_id = get_current_company_id();
    if (!$company_id) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT subscription_plan, subscription_expires_at, status 
        FROM companies 
        WHERE id = ?
    ");
    $stmt->execute([$company_id]);
    return $stmt->fetch();
}
