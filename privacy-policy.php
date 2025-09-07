<?php
require_once __DIR__ . '/config/auth.php';
$pageTitle = 'Privacy Policy - Backlink Manager';
require_once __DIR__ . '/includes/header_minimal.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<div class="container py-5" style="padding-top: 120px !important;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="display-4 mb-4">Privacy Policy</h1>
            <p class="text-muted mb-5">Last updated: <?= date('F d, Y') ?></p>

            <div class="content">
                <section class="mb-5">
                    <h2 class="h4 mb-4">1. Information We Collect</h2>
                    <p>We collect information that you provide directly to us, including:</p>
                    <ul class="mb-4">
                        <li>Name and contact information</li>
                        <li>Account credentials</li>
                        <li>Billing information</li>
                        <li>Website and backlink data</li>
                    </ul>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">2. How We Use Your Information</h2>
                    <p>We use the information we collect to:</p>
                    <ul class="mb-4">
                        <li>Provide and maintain our services</li>
                        <li>Process your transactions</li>
                        <li>Send you technical notices and support messages</li>
                        <li>Communicate with you about products, services, and events</li>
                    </ul>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">3. Data Security</h2>
                    <p>We implement appropriate technical and organizational security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">4. Cookies and Tracking</h2>
                    <p>We use cookies and similar tracking technologies to track activity on our service and hold certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.</p>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">5. Your Rights</h2>
                    <p>You have the right to:</p>
                    <ul class="mb-4">
                        <li>Access your personal data</li>
                        <li>Correct inaccurate data</li>
                        <li>Request deletion of your data</li>
                        <li>Object to our use of your data</li>
                    </ul>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">6. Contact Us</h2>
                    <p>If you have any questions about this Privacy Policy, please contact us at:</p>
                    <ul class="list-unstyled">
                        <li>Email: privacy@backlinksvalidator.com</li>
                        <li>Website: backlinksvalidator.com</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
    .content {
        font-size: 1.1rem;
        line-height: 1.8;
    }

    .content ul {
        list-style-type: disc;
        padding-left: 1.5rem;
    }

    .content section {
        padding: 1rem;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
</style>

<?php include_once __DIR__ . '/includes/footer.php'; ?>