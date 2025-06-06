<?php
//require_once __DIR__ . '/config/db.php';
//require_once __DIR__ . '/config/validationHelper.php';

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/auth/google/functions/GoogleAuth.php';

$errors = [];
$pageTitle = 'Login';

// Initialize Google Auth
$googleAuth = new GoogleAuth();
$googleAuthUrl = $googleAuth->getAuthUrl();

// Check for error messages from process_login.php
if (isset($_GET['error'])) {
    $errors[] = htmlspecialchars($_GET['error']);
}

include_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center min-vh-100 bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link mb-3" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                        <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
                    </svg>
                    <h1 class="h3 mb-2">Welcome Back!</h1>
                    <p class="text-muted">Please sign in to your account</p>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div id="error-messages" class="alert alert-danger" style="display: none;">
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form id="login-form" method="POST" action="process_login.php" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="email" class="form-label">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-mail me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                                            <path d="M3 7l9 6l9 -6" />
                                        </svg>
                                        Email Address
                                    </div>
                                </label>
                                <div class="input-group">
                                    <input type="email" class="form-control form-control-lg" id="email" name="email" required
                                        placeholder="Enter your email">
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z" />
                                            <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
                                            <path d="M8 11v-4a4 4 0 1 1 8 0v4" />
                                        </svg>
                                        Password
                                    </div>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" required
                                        placeholder="Enter your password">
                                    <div class="invalid-feedback">Please enter your password.</div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <span class="d-flex align-items-center justify-content-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-login me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
                                            <path d="M20 12h-13l3 -3m0 6l-3 -3" />
                                        </svg>
                                        Sign In
                                    </span>
                                </button>

                                <div class="text-center my-3">
                                    <span class="text-muted">OR</span>
                                </div>

                                <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" class="btn btn-outline-secondary btn-lg">
                                    <span class="d-flex align-items-center justify-content-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-google me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M17.788 5.108a9 9 0 1 0 3.212 6.892h-8" />
                                        </svg>
                                        Continue with Google
                                    </span>
                                </a>
                            </div>

                            <div class="text-center">
                                <p class="text-muted mb-0">
                                    Don't have an account?
                                    <a href="register.php" class="text-decoration-none">
                                        Create one now
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-right ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M5 12l14 0" />
                                            <path d="M13 18l6 -6" />
                                            <path d="M13 6l6 6" />
                                        </svg>
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <small class="text-muted">
                        © <?= date('Y') ?> Backlink Manager. All rights reserved.
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
    .btn-primary {
        background: linear-gradient(135deg, #0061f2 0%, #0044c2 100%);
        border: none;
        transition: transform 0.2s;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 97, 242, 0.2);
        background: linear-gradient(135deg, #0044c2 0%, #0061f2 100%);
    }

    .form-control {
        border: 1px solid #e0e5ec;
        padding: 0.75rem 1rem;
        transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .form-control:focus {
        border-color: #0061f2;
        box-shadow: 0 0 0 0.25rem rgba(0, 97, 242, 0.1);
    }

    .card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .bg-light {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%) !important;
    }
</style>

<script>
    document.getElementById('login-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const errorDiv = document.getElementById('error-messages');
        const submitButton = this.querySelector('button[type="submit"]');

        // Disable submit button and show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = 'Logging in...';

        fetch('process_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect on success
                    window.location.href = data.redirect;
                } else {
                    // Show error message
                    errorDiv.style.display = 'block';
                    errorDiv.innerHTML = `<p>${data.message}</p>`;

                    // Re-enable submit button
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Login';
                }
            })
            .catch(error => {
                errorDiv.style.display = 'block';
                errorDiv.innerHTML = '<p>An error occurred. Please try again.</p>';

                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.innerHTML = 'Login';
            });
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>