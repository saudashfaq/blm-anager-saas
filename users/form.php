<?php
require_once __DIR__ . '/../middleware.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'User Form';
$bodyClass = 'page';

if ($_SESSION['role'] !== 'admin') {
    header("Location:" . BASE_URL . "index.php");
    exit;
}

$id = $username = $email = $role = "";
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch();
    if ($user) {
        $id = $user['id'];
        $username = $user['username'];
        $email = $user['email'];
        $role = $user['role'];
    }
}

$pageTitle = $id ? 'Edit User' : 'Add User';

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-<?= $id ? 'edit' : 'plus' ?> me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <?php if ($id): ?>
                                <!-- icon-tabler-user-edit -->
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                <path d="M6 21v-2a4 4 0 0 1 4 -4h3.5" />
                                <path d="M18.5 15.5l2.5 2.5" />
                                <path d="M21 12.5l-2.5 2.5l-3 -3l2.5 -2.5z" />
                            <?php else: ?>
                                <!-- icon-tabler-user-plus -->
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                <path d="M16 19h6" />
                                <path d="M19 16v6" />
                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                            <?php endif; ?>
                        </svg>
                        <?= $pageTitle ?>
                    </h2>
                </div>
                <div class="card-body">
                    <!-- Alert container for success/error messages -->
                    <div class="alert alert-important alert-success alert-dismissible" role="alert" style="display: none;">
                        <div class="d-flex">
                            <div>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="icon alert-icon"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    fill="none"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M5 12l5 5l10 -10"></path>
                                </svg>
                            </div>
                            <div id="alert-message">Wow! Everything worked!</div>
                        </div>
                        <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>

                    <form id="user-form" method="POST">
                        <input type="hidden" name="id" value="<?= $id ?? '' ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="mb-3">
                            <label class="form-label">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <circle cx="12" cy="7" r="4" />
                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                </svg>
                                Username
                            </label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
                            <span class="error-message text-danger" style="display: none;"></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-mail me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <path d="M3 7l9 6l9 -6" />
                                </svg>
                                Email
                            </label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                            <span class="error-message text-danger" style="display: none;"></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <rect x="5" y="11" width="14" height="10" rx="2" />
                                    <circle cx="12" cy="16" r="1" />
                                    <path d="M8 11v-4a4 4 0 0 1 8 0v4" />
                                </svg>
                                Password <?= $id ? "(Leave blank to keep unchanged)" : "" ?>
                            </label>
                            <input type="password" name="password" class="form-control" <?= $id ? "" : "required" ?>>
                            <span class="error-message text-danger" style="display: none;"></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shield-check me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M11.46 3.867a12 12 0 0 0 8.54 3.133c0 6 -2 12 -7 12s-7 -6 -7 -12a12 12 0 0 0 8.54 -3.133z" />
                                    <path d="M15 9l-3 3l-1.5 -1.5" />
                                </svg>
                                Role
                            </label>
                            <select name="role" class="form-select" required>
                                <option value="admin" <?= $role === "admin" ? "selected" : "" ?>>Admin</option>
                                <option value="user" <?= $role === "user" ? "selected" : "" ?>>User</option>
                            </select>
                            <span class="error-message text-danger" style="display: none;"></span>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M5 12l5 5l10 -10" />
                                </svg>
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Include user form specific JavaScript -->
<script src="<?= defined('BASE_URL') ? BASE_URL : '/' ?>includes/js/user-form.js"></script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>