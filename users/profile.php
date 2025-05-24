<?php
require_once __DIR__ . '/../middleware.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/validationHelper.php';

$pageTitle = 'Update Profile';
$bodyClass = 'page';

// Ensure the user is an admin
if ($_SESSION['role'] !== 'admin') {
    header("Location:" . BASE_URL . "index.php");
    exit;
}

// Fetch the logged-in admin's details
$userId = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ? AND company_id = ?");
$stmt->execute([$userId, $company_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location:" . BASE_URL . "index.php");
    exit;
}

$username = $user['username'];
$email = $user['email'];

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Update Profile</h2>
        </div>
        <div class="card-body">
            <!-- Alert container for success/error messages -->
            <div id="alert-container" class="mb-3" style="display: none;">
                <div id="alert-message" class="alert alert-dismissible" role="alert">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>

            <form id="profile-form" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
                    <span class="error-message text-danger" style="display: none;"></span>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                    <span class="error-message text-danger" style="display: none;"></span>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-success">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include profile management specific JavaScript -->
<script src="<?= defined('BASE_URL') ? BASE_URL : '/' ?>includes/js/profile.js"></script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>