<?php

session_start();

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/subscription_plans.php';

// Check if BASE_URL is defined, if not redirect to installation page
if (!defined('BASE_URL')) {
    header("Location: install/index.php");
    exit;
}

// Handle authenticated users
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === 1) {
        header("Location:" . BASE_URL . "super-admin/subscribers.php");
        exit;
    }

    if (
        isset($_SESSION['subscription']) &&
        isset($_SESSION['subscription']['plan_name']) &&
        $_SESSION['subscription']['plan_name'] === PLAN_FREE
    ) {
        header("Location:" . BASE_URL . "subscriptions/subscribe.php");
        exit;
    }

    header("Location:" . BASE_URL . "dashboard.php");
    exit;
}

// Get displayable plans for pricing section
$displayablePlans = array_filter(SUBSCRIPTION_LIMITS, function ($plan) {
    return $plan['display'] ?? false;
});

$pageTitle = 'Backlink Manager - Professional Backlink Management Solution';

// Use a minimal header without the default navbar
require_once __DIR__ . '/includes/header_minimal.php';

// Include the public navbar
require_once __DIR__ . '/includes/public_navbar.php';
?>

<!-- Hero Section -->
<div class="hero position-relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="position-absolute w-100 h-100 top-0 start-0 bg-pattern"></div>

    <!-- Gradient Overlay -->
    <div class="position-absolute w-100 h-100 top-0 start-0 hero-gradient"></div>

    <div class="container position-relative py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="pe-lg-5">
                    <h1 class="display-4 fw-bold text-white mb-4 hero-title" data-aos="fade-up">
                        Supercharge Your <span class="text-warning">SEO Strategy</span> with Smart Backlink Management
                    </h1>
                    <p class="lead text-white-90 mb-4" data-aos="fade-up" data-aos-delay="100">
                        Track, analyze, and optimize your backlink portfolio with our comprehensive solution. Get real-time insights and boost your website's authority.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-3" data-aos="fade-up" data-aos-delay="200">
                        <a href="register.php" class="btn btn-lg btn-warning">
                            Start Free Trial
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-right ms-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12l14 0" />
                                <path d="M13 18l6 -6" />
                                <path d="M13 6l6 6" />
                            </svg>
                        </a>
                        <a href="#features" class="btn btn-lg btn-outline-light">
                            Explore Features
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chevron-down ms-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M6 9l6 6l6 -6" />
                            </svg>
                        </a>
                    </div>

                    <!-- Trust Indicators -->
                    <div class="mt-5" data-aos="fade-up" data-aos-delay="300">
                        <div class="d-flex align-items-center gap-4 flex-wrap">
                            <div class="text-white-75">
                                <div class="h4 mb-0 text-white">10K+</div>
                                <small>Active Users</small>
                            </div>
                            <div class="text-white-75">
                                <div class="h4 mb-0 text-white">1M+</div>
                                <small>Backlinks Tracked</small>
                            </div>
                            <div class="text-white-75">
                                <div class="h4 mb-0 text-white">99.9%</div>
                                <small>Uptime</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6" data-aos="fade-left">
                <div class="position-relative">
                    <!-- Main Dashboard Image -->
                    <div class="dashboard-preview">
                        <img src="<?= BASE_URL ?>assets/images/dashboard-preview.png" alt="Backlink Manager Dashboard" class="img-fluid rounded-4 shadow-lg">
                    </div>

                    <!-- Floating Elements -->
                    <div class="floating-card floating-card-1">
                        <div class="d-flex align-items-center bg-white rounded-3 shadow-lg p-3">
                            <div class="icon-box bg-success-subtle rounded-2 p-2 me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trending-up text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M3 17l6 -6l4 4l8 -8" />
                                    <path d="M14 7l7 0l0 7" />
                                </svg>
                            </div>
                            <div>
                                <div class="h6 mb-0">Domain Authority</div>
                                <div class="text-success">+12.5% <small class="text-muted">vs last month</small></div>
                            </div>
                        </div>
                    </div>

                    <div class="floating-card floating-card-2">
                        <div class="bg-white rounded-3 shadow-lg p-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="icon-box bg-primary-subtle rounded-2 p-2 me-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link text-primary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                                        <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
                                    </svg>
                                </div>
                                <div class="h6 mb-0">New Backlinks</div>
                            </div>
                            <div class="display-6 fw-bold text-primary">127</div>
                            <small class="text-muted">Last 7 days</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<section id="features" class="py-5" style="padding-top: 120px !important;">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Powerful Features for SEO Success</h2>
            <p class="lead text-muted">Everything you need to manage and optimize your backlink strategy</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-primary bg-gradient text-white rounded-3 mb-3 p-3 d-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chart-line" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 19l16 0" />
                                <path d="M4 15l4 -6l4 2l4 -5l4 4" />
                            </svg>
                        </div>
                        <h3 class="h5 mb-3">Real-time Monitoring</h3>
                        <p class="text-muted mb-0">Track your backlinks' status and performance in real-time with automated verification.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-success bg-gradient text-white rounded-3 mb-3 p-3 d-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-upload" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                <path d="M7 9l5 -5l5 5" />
                                <path d="M12 4l0 12" />
                            </svg>
                        </div>
                        <h3 class="h5 mb-3">Bulk Upload</h3>
                        <p class="text-muted mb-0">Import multiple backlinks at once and manage them efficiently in campaigns.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-info bg-gradient text-white rounded-3 mb-3 p-3 d-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-report-analytics" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                                <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                <path d="M9 17v-5" />
                                <path d="M12 17v-1" />
                                <path d="M15 17v-3" />
                            </svg>
                        </div>
                        <h3 class="h5 mb-3">Advanced Analytics</h3>
                        <p class="text-muted mb-0">Get detailed reports and insights about your backlink performance and SEO impact.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section id="testimonials" class="py-5 bg-gradient-light" style="padding-top: 120px !important;">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">What Our Clients Say</h2>
            <p class="lead text-muted">Trusted by SEO professionals worldwide</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4" data-aos="fade-up">
                <div class="card h-100 border-0 shadow-sm testimonial-card">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-quote text-primary" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M10 11h-4a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1h3a1 1 0 0 1 1 1v6c0 2.667 -1.333 4.333 -4 5" />
                                <path d="M19 11h-4a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1h3a1 1 0 0 1 1 1v6c0 2.667 -1.333 4.333 -4 5" />
                            </svg>
                        </div>
                        <p class="mb-4 text-muted">"This tool has transformed how we manage our backlink portfolio. The real-time monitoring and detailed analytics have helped us improve our SEO strategy significantly."</p>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle bg-primary text-white me-3">JS</div>
                            <div>
                                <h5 class="mb-1">John Smith</h5>
                                <p class="small text-muted mb-0">SEO Director at TechCorp</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card h-100 border-0 shadow-sm testimonial-card">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-quote text-primary" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M10 11h-4a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1h3a1 1 0 0 1 1 1v6c0 2.667 -1.333 4.333 -4 5" />
                                <path d="M19 11h-4a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1h3a1 1 0 0 1 1 1v6c0 2.667 -1.333 4.333 -4 5" />
                            </svg>
                        </div>
                        <p class="mb-4 text-muted">"The bulk upload feature and campaign management system have saved us countless hours. It's now much easier to track and optimize our backlink strategy."</p>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle bg-success text-white me-3">EW</div>
                            <div>
                                <h5 class="mb-1">Emma Wilson</h5>
                                <p class="small text-muted mb-0">Marketing Manager at GrowthCo</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 border-0 shadow-sm testimonial-card">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-quote text-primary" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M10 11h-4a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1h3a1 1 0 0 1 1 1v6c0 2.667 -1.333 4.333 -4 5" />
                                <path d="M19 11h-4a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1h3a1 1 0 0 1 1 1v6c0 2.667 -1.333 4.333 -4 5" />
                            </svg>
                        </div>
                        <p class="mb-4 text-muted">"The detailed analytics and reporting features provide invaluable insights. We've seen a 40% improvement in our backlink quality since using this platform."</p>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle bg-info text-white me-3">MR</div>
                            <div>
                                <h5 class="mb-1">Michael Rodriguez</h5>
                                <p class="small text-muted mb-0">CEO at Digital Boost</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="py-5 bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Choose Your Plan</h2>
            <p class="lead text-muted">Flexible options for businesses of all sizes</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($displayablePlans as $planName => $plan): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm <?= $plan['highlight'] ? 'border border-primary border-2' : '' ?>">
                        <div class="card-body p-4">
                            <?php if ($plan['highlight']): ?>
                                <div class="ribbon bg-primary text-white position-absolute end-0 top-0 px-3 py-1">Popular</div>
                            <?php endif; ?>

                            <h3 class="h4 mb-3"><?= htmlspecialchars($plan['name']) ?></h3>
                            <p class="text-muted mb-4"><?= htmlspecialchars($plan['description']) ?></p>

                            <ul class="list-unstyled mb-4">
                                <li class="mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check text-success me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M5 12l5 5l10 -10" />
                                    </svg>
                                    <?= $plan['max_campaigns'] === -1 ? 'Unlimited' : $plan['max_campaigns'] ?> Campaigns
                                </li>
                                <li class="mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check text-success me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M5 12l5 5l10 -10" />
                                    </svg>
                                    <?= $plan['max_total_backlinks'] === -1 ? 'Unlimited' : $plan['max_total_backlinks'] ?> Total Backlinks
                                </li>
                                <?php foreach ($plan['features'] as $feature => $details): ?>
                                    <li class="mb-2">
                                        <?php if ($details['available']): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check text-success me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M5 12l5 5l10 -10" />
                                            </svg>
                                        <?php else: ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x text-danger me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M18 6l-12 12" />
                                                <path d="M6 6l12 12" />
                                            </svg>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($details['description']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <a href="register.php" class="btn btn-primary d-block">
                                Get Started
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-right ms-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M5 12l14 0" />
                                    <path d="M13 18l6 -6" />
                                    <path d="M13 6l6 6" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="display-5 fw-bold mb-4">Ready to Optimize Your Backlink Strategy?</h2>
                <p class="lead text-muted mb-4">Join thousands of satisfied customers who trust our platform for their SEO success.</p>
                <a href="register.php" class="btn btn-primary btn-lg">
                    Start Your Free Trial
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-right ms-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M5 12l14 0" />
                        <path d="M13 18l6 -6" />
                        <path d="M13 6l6 6" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer bg-dark text-white py-5 mt-5">
    <div class="container py-4">
        <div class="row g-4">
            <!-- Company Info -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand d-flex align-items-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                        <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
                    </svg>
                    <span class="h4 text-white mb-0">Backlink Manager</span>
                </div>
                <p class="text-white-50 mb-4">Empowering businesses with smart backlink management solutions. Track, analyze, and optimize your backlink portfolio with ease.</p>
                <div class="social-links">
                    <a href="#" class="btn btn-icon btn-sm btn-ghost-light rounded-circle me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-twitter" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M22 4.01c-1 .49 -1.98 .689 -3 .99c-1.121 -1.265 -2.783 -1.335 -4.38 -.737s-2.643 2.06 -2.62 3.737v1c-3.245 .083 -6.135 -1.395 -8 -4c0 0 -4.182 7.433 4 11c-1.872 1.247 -3.739 2.088 -6 2c3.308 1.803 6.913 2.423 10.034 1.517c3.58 -1.04 6.522 -3.723 7.651 -7.742a13.84 13.84 0 0 0 .497 -3.753c0 -.249 1.51 -2.772 1.818 -4.013z"></path>
                        </svg>
                    </a>
                    <a href="#" class="btn btn-icon btn-sm btn-ghost-light rounded-circle me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-linkedin" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"></path>
                            <path d="M8 11l0 5"></path>
                            <path d="M8 8l0 .01"></path>
                            <path d="M12 16l0 -5"></path>
                            <path d="M16 16v-3a2 2 0 0 0 -4 0"></path>
                        </svg>
                    </a>
                    <a href="#" class="btn btn-icon btn-sm btn-ghost-light rounded-circle me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-github" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M9 19c-4.3 1.4 -4.3 -2.5 -6 -3m12 5v-3.5c0 -1 .1 -1.4 -.5 -2c2.8 -.3 5.5 -1.4 5.5 -6a4.6 4.6 0 0 0 -1.3 -3.2a4.2 4.2 0 0 0 -.1 -3.2s-1.1 -.3 -3.5 1.3a12.3 12.3 0 0 0 -6.2 0c-2.4 -1.6 -3.5 -1.3 -3.5 -1.3a4.2 4.2 0 0 0 -.1 3.2a4.6 4.6 0 0 0 -1.3 3.2c0 4.6 2.7 5.7 5.5 6c-.6 .6 -.6 1.2 -.5 2v3.5"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-3 col-md-6">
                <h5 class="text-white mb-4">Quick Links</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a href="#features" class="text-white-50 text-decoration-none hover-white">Features</a>
                    </li>
                    <li class="mb-2">
                        <a href="#pricing" class="text-white-50 text-decoration-none hover-white">Pricing</a>
                    </li>
                    <li class="mb-2">
                        <a href="#testimonials" class="text-white-50 text-decoration-none hover-white">Testimonials</a>
                    </li>
                    <li class="mb-2">
                        <a href="register.php" class="text-white-50 text-decoration-none hover-white">Get Started</a>
                    </li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-lg-5 col-md-6">
                <h5 class="text-white mb-4">Stay Updated</h5>
                <p class="text-white-50 mb-4">Subscribe to our newsletter for the latest updates and SEO insights.</p>
                <form class="mb-3">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Enter your email" aria-label="Enter your email">
                        <button class="btn btn-primary" type="submit">Subscribe</button>
                    </div>
                </form>
                <p class="text-white-50 small mb-0">By subscribing, you agree to our Privacy Policy and consent to receive updates from our company.</p>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-top border-white-10 mt-4">
        <div class="container">
            <div class="row py-4">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-white-50 mb-0">&copy; <?= date('Y') ?> Backlink Manager. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item">
                            <a href="privacy-policy.php" class="text-white-50 text-decoration-none hover-white">Privacy Policy</a>
                        </li>
                        <li class="list-inline-item ms-3">
                            <a href="terms-of-service.php" class="text-white-50 text-decoration-none hover-white">Terms of Service</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Hero Section Styles */
    .hero {
        background-color: #0B1437;
        overflow: hidden;
        position: relative;
        padding-top: 120px;
        /* Increased padding for fixed navbar */
    }

    .hero-gradient {
        background: linear-gradient(135deg,
                rgba(13, 22, 60, 0.85) 0%,
                rgba(11, 20, 55, 0.75) 100%);
        z-index: 1;
    }

    .bg-pattern {
        opacity: 0.1;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .container.position-relative {
        z-index: 2;
    }

    .hero-title {
        font-size: 3.5rem;
        line-height: 1.2;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        position: relative;
    }

    /* Floating Cards */
    .dashboard-preview {
        position: relative;
        z-index: 3;
    }

    .floating-card {
        position: absolute;
        z-index: 4;
        animation: float 3s ease-in-out infinite;
    }

    .floating-card-1 {
        top: 10%;
        right: -30px;
        animation-delay: 0.5s;
    }

    .floating-card-2 {
        bottom: 10%;
        left: -30px;
        animation-delay: 1s;
    }

    .icon-box {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @keyframes float {
        0% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-10px);
        }

        100% {
            transform: translateY(0px);
        }
    }

    /* Button Enhancements */
    .btn-warning {
        background: linear-gradient(135deg, #FFB017 0%, #FF9900 100%);
        border: none;
        color: #000;
        font-weight: 600;
        position: relative;
        z-index: 5;
    }

    .btn-outline-light {
        position: relative;
        z-index: 5;
        backdrop-filter: blur(4px);
        background: rgba(255, 255, 255, 0.1);
    }

    .btn-outline-light:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-1px);
    }

    /* Trust Indicators Enhancement */
    .text-white-75 {
        position: relative;
        z-index: 3;
        backdrop-filter: blur(4px);
        padding: 0.5rem;
        border-radius: 0.5rem;
        background: rgba(255, 255, 255, 0.05);
    }

    .text-white-75 .h4 {
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    .text-white-90 {
        color: rgba(255, 255, 255, 0.95);
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    /* Responsive Adjustments */
    @media (max-width: 991.98px) {
        .hero-title {
            font-size: 2.5rem;
        }

        .floating-card {
            display: none;
        }

        .min-vh-75 {
            min-height: auto;
        }
    }

    @media (max-width: 768px) {
        .display-4 {
            font-size: 2.5rem;
        }

        .display-5 {
            font-size: 2rem;
        }

        .hero {
            text-align: center;
        }

        .hero .btn-lg {
            width: 100%;
            margin-bottom: 1rem;
        }
    }

    /* Footer Styles */
    .footer {
        background-color: #0B1437;
        position: relative;
    }

    .footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.1) 50%, rgba(255, 255, 255, 0) 100%);
    }

    .hover-white {
        transition: color 0.2s ease;
    }

    .hover-white:hover {
        color: #fff !important;
    }

    .btn-ghost-light {
        color: rgba(255, 255, 255, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.2s ease;
    }

    .btn-ghost-light:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .border-white-10 {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    .footer .form-control {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .footer .form-control:focus {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
        color: #fff;
        box-shadow: none;
    }

    .footer .form-control::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    @media (max-width: 767.98px) {
        .list-inline-item.ms-3 {
            margin-left: 1rem !important;
        }
    }

    /* Add smooth scroll behavior */
    html {
        scroll-behavior: smooth;
    }
</style>

<!-- Add AOS Library for animations -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>