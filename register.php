<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/validationHelper.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new ValidationHelper($_POST);

    // Validate company details
    $validator
        ->required('company_name', 'Company name is required')
        ->minLength('company_name', 2, 'Company name must be at least 2 characters')
        ->maxLength('company_name', 255, 'Company name must not exceed 255 characters')
        ->required('company_email', 'Company email is required')
        ->email('company_email', 'Please enter a valid company email')
        ->required('company_phone', 'Company phone is required')
        ->regex('company_phone', '/^[0-9+\-\s()]{10,20}$/', 'Please enter a valid phone number');

    // Validate admin user details
    $validator
        ->required('username', 'Username is required')
        ->minLength('username', 3, 'Username must be at least 3 characters')
        ->maxLength('username', 50, 'Username must not exceed 50 characters')
        ->regex('username', '/^[a-zA-Z0-9_]+$/', 'Username can only contain letters, numbers, and underscores')
        ->required('email', 'Email is required')
        ->email('email', 'Please enter a valid email address')
        ->maxLength('email', 255, 'Email must not exceed 255 characters')
        ->required('password', 'Password is required')
        ->minLength('password', 8, 'Password must be at least 8 characters')
        ->regex('password', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', 'Password must contain at least one uppercase letter, one lowercase letter, and one number');

    if ($validator->passes()) {
        try {
            $pdo->beginTransaction();

            // Create company
            $stmt = $pdo->prepare("INSERT INTO companies (name, email, phone, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                $_POST['company_name'],
                $_POST['company_email'],
                $_POST['company_phone']
            ]);
            $company_id = $pdo->lastInsertId();

            // Create admin user
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (company_id, username, email, password, role, created_at) VALUES (?, ?, ?, ?, 'admin', NOW())");
            $stmt->execute([
                $company_id,
                $_POST['username'],
                $_POST['email'],
                $hashedPassword
            ]);

            $pdo->commit();
            $success = true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Registration failed. Please try again.';
            error_log($e->getMessage());
        }
    } else {
        $errors = $validator->getErrors();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Backlink Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Register Your Company</h2>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Registration successful! You can now <a href="login.php">login</a>.
                            </div>
                        <?php else: ?>
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <h4 class="mb-3">Company Information</h4>
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="company_email" class="form-label">Company Email</label>
                                    <input type="email" class="form-control" id="company_email" name="company_email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="company_phone" class="form-label">Company Phone</label>
                                    <input type="tel" class="form-control" id="company_phone" name="company_phone" required>
                                </div>

                                <h4 class="mb-3 mt-4">Admin Account</h4>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Password must be at least 8 characters and contain uppercase, lowercase, and numbers.</div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Register</button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            Already have an account? <a href="login.php">Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>