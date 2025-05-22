<?php
require_once __DIR__ . '/config/config.php';
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
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="d-flex align-items-center min-vh-100" style="padding-top: 120px !important;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="text-center mb-5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link mb-3" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                            <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
                        </svg>
                        <h1 class="h2 mb-2">Create Your Account</h1>
                        <p class="text-muted">Start managing your backlinks professionally</p>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <div class="d-flex">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-circle-check me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                            <path d="M9 12l2 2l4 -4" />
                                        </svg>
                                        <div>
                                            Registration successful! You can now <a href="login.php" class="fw-bold">login</a>.
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger">
                                        <div class="d-flex">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-alert-circle me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                                <path d="M12 8l0 4" />
                                                <path d="M12 16l.01 0" />
                                            </svg>
                                            <div>
                                                <ul class="mb-0 list-unstyled">
                                                    <?php foreach ($errors as $error): ?>
                                                        <li><?php echo htmlspecialchars($error); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="" class="needs-validation" novalidate>
                                    <div class="mb-4">
                                        <h3 class="card-title mb-3">Company Information</h3>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Company Name</label>
                                                <input type="text" class="form-control form-control-lg" name="company_name" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Company Email</label>
                                                <input type="email" class="form-control form-control-lg" name="company_email" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Company Phone</label>
                                                <input type="tel" class="form-control form-control-lg" name="company_phone" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <h3 class="card-title mb-3">Admin Account</h3>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Username</label>
                                                <input type="text" class="form-control form-control-lg" name="username" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control form-control-lg" name="email" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control form-control-lg" name="password" required>
                                                </div>
                                                <div class="form-text">Must be 8+ characters with uppercase, lowercase & numbers</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-footer">
                                        <button type="submit" class="btn btn-primary btn-lg w-100">
                                            Create Account
                                        </button>

                                        <div class="text-center my-3">
                                            <span class="text-muted">OR</span>
                                        </div>

                                        <a href="auth/google/functions/google_callback.php" class="btn btn-outline-secondary btn-lg w-100">
                                            <span class="d-flex align-items-center justify-content-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-google me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M17.788 5.108a9 9 0 1 0 3.212 6.892h-8" />
                                                </svg>
                                                Sign up with Google
                                            </span>
                                        </a>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <div class="text-center mt-4">
                                Already have an account? <a href="login.php" class="text-decoration-none">Sign in</a>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            By creating an account, you agree to our
                            <a href="terms-of-service.php" class="text-decoration-none">Terms of Service</a> and
                            <a href="privacy-policy.php" class="text-decoration-none">Privacy Policy</a>
                        </small>
                        <div class="mt-3">
                            <a href="index.php" class="text-decoration-none text-muted">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chevron-left" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M15 6l-6 6l6 6" />
                                </svg>
                                Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%) !important;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
        }

        .form-control {
            border: 1px solid #e0e5ec;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--tblr-primary);
            box-shadow: 0 0 0 0.25rem rgba(var(--tblr-primary-rgb), 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--tblr-primary) 0%, #0044c2 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            transition: all 0.2s ease-in-out;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.5rem 1.5rem rgba(var(--tblr-primary-rgb), 0.2);
        }

        .icon-tabler {
            stroke-width: 1.5;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>