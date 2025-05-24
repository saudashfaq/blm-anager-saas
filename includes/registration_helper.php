<?php

/**
 * Helper functions for user registration and session management
 */

/**
 * Initialize user session with required data
 */
function initializeUserSession($userId, $companyId)
{
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Set essential session variables
    $_SESSION['user_id'] = $userId;
    $_SESSION['company_id'] = $companyId;
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();

    // Get user data for additional session info
    global $pdo;
    $stmt = $pdo->prepare("SELECT username, email, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $_SESSION['username'] = $userData['username'];
        $_SESSION['email'] = $userData['email'];
        $_SESSION['role'] = $userData['role'];
    }

    // Get company data
    $stmt = $pdo->prepare("SELECT name as company_name FROM companies WHERE id = ?");
    $stmt->execute([$companyId]);
    $companyData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($companyData) {
        $_SESSION['company_name'] = $companyData['company_name'];
    }
}

function registerUser($userData)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Create company
        $stmt = $pdo->prepare("INSERT INTO companies (name, email, phone, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([
            $userData['company_name'],
            $userData['company_email'],
            $userData['company_phone'] ?? ''  // Make phone optional for Google auth
        ]);
        $company_id = $pdo->lastInsertId();

        // Create admin user
        $hashedPassword = isset($userData['password']) ?
            password_hash($userData['password'], PASSWORD_DEFAULT) :
            password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (company_id, username, email, password, role, created_at) VALUES (?, ?, ?, ?, 'admin', NOW())");
        $stmt->execute([
            $company_id,
            $userData['username'],
            $userData['email'],
            $hashedPassword
        ]);

        $user_id = $pdo->lastInsertId();
        $pdo->commit();

        // Initialize session for the new user
        initializeUserSession($user_id, $company_id);

        // Send welcome email
        require_once __DIR__ . '/../emails/send_email.php';
        $mailService = new MailService();

        $emailBody = "
            <h2>Welcome to Backlink Manager!</h2>
            <p>Dear {$userData['username']},</p>
            <p>Thank you for registering with Backlink Manager. Your account has been successfully created.</p>
            <p><strong>Company Details:</strong></p>
            <ul>
                <li>Company Name: {$userData['company_name']}</li>
                <li>Company Email: {$userData['company_email']}</li>
            </ul>
            <p><strong>Your Account Details:</strong></p>
            <ul>
                <li>Username: {$userData['username']}</li>
                <li>Email: {$userData['email']}</li>
                <li>Role: Admin</li>
            </ul>
            <p>You can now log in to your account and start managing your backlinks.</p>
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            <p>Best regards,<br>The Backlink Manager Team</p>
        ";

        $mailService->send(
            $userData['email'],
            'Welcome to Backlink Manager!',
            $emailBody,
            true // Send as HTML
        );

        return [
            'success' => true,
            'user_id' => $user_id,
            'company_id' => $company_id
        ];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        return [
            'success' => false,
            'error' => 'Registration failed. Please try again.'
        ];
    } catch (Exception $e) {
        error_log("Error sending welcome email: " . $e->getMessage());
        // Still return success since the user was created
        return [
            'success' => true,
            'user_id' => $user_id,
            'company_id' => $company_id,
            'warning' => 'Account created successfully but welcome email could not be sent.'
        ];
    }
}
