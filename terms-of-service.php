<?php
require_once __DIR__ . '/config/auth.php';
$pageTitle = 'Terms of Service - Backlink Manager';
require_once __DIR__ . '/includes/header_minimal.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<div class="container py-5" style="padding-top: 120px !important;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="display-4 mb-4">Terms of Service</h1>
            <p class="text-muted mb-5">Last updated: <?= date('F d, Y') ?></p>

            <div class="content">
                <section class="mb-5">
                    <h2 class="h4 mb-4">1. Acceptance of Terms</h2>
                    <p>By accessing and using Backlink Manager, you accept and agree to be bound by the terms and provision of this agreement.</p>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">2. Description of Service</h2>
                    <p>Backlink Manager provides tools and services for managing and monitoring backlinks. We reserve the right to modify or discontinue the service at any time.</p>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">3. User Responsibilities</h2>
                    <p>You agree to:</p>
                    <ul class="mb-4">
                        <li>Provide accurate account information</li>
                        <li>Maintain the security of your account</li>
                        <li>Use the service in compliance with all applicable laws</li>
                        <li>Not engage in any automated or bulk access to the service</li>
                    </ul>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">4. Subscription and Payments</h2>
                    <p>Users must maintain an active subscription to access premium features. Subscriptions are billed in advance on a recurring basis. You can cancel your subscription at any time.</p>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">5. Limitation of Liability</h2>
                    <p>Backlink Manager shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the service.</p>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">6. Intellectual Property</h2>
                    <p>All content and materials available on Backlink Manager are protected by intellectual property rights. You may not use, reproduce, or distribute any content from our service without our permission.</p>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">7. Termination</h2>
                    <p>We may terminate or suspend your account and access to the service at our sole discretion, without prior notice, for conduct that we believe violates these Terms or is harmful to other users, us, or third parties.</p>
                </section>

                <section class="mb-5">
                    <h2 class="h4 mb-4">8. Contact Information</h2>
                    <p>For any questions regarding these Terms, please contact us at:</p>
                    <ul class="list-unstyled">
                        <li>Email: support@backlinksvalidator.com</li>
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