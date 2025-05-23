<?php
require_once __DIR__ . '/../config/subscription_plans.php';
?>
<nav class="navbar navbar-expand navbar-light">
    <div class="container-fluid">
        <!-- Navbar Brand -->
        <a class="navbar-brand" href="#">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
            </svg>
            Backlink Manager
        </a>

        <!-- Toggler for mobile view -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-center">
                <?php
                // Check if user is on free plan - with proper fallback
                $isFreePlan = false;
                if (
                    isset($_SESSION['subscription']) &&
                    isset($_SESSION['subscription']['plan_name']) &&
                    $_SESSION['subscription']['plan_name'] === PLAN_FREE
                ) {
                    $isFreePlan = true;
                }

                $isSuperAdmin = isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === 1;

                if ($isSuperAdmin) {
                    // Show only superadmin links
                ?>
                    <a class="nav-item nav-link <?= (basename($_SERVER['PHP_SELF']) == 'subscribers.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>super-admin/subscribers.php">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users-plus me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                            <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                            <path d="M16 11h6m-3 -3v6" />
                        </svg>
                        Subscribers
                    </a>
                    <a class="nav-item nav-link <?= (basename($_SERVER['PHP_SELF']) == 'proxymanager.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>super-admin/proxymanager.php">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shield me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                        </svg>
                        Proxy Management
                    </a>
                    <a class="nav-item nav-link <?= (basename($_SERVER['PHP_SELF']) == 'assign_plan.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>super-admin/assign_plan.php">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-adjustments me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <circle cx="6" cy="10" r="2" />
                            <line x1="6" y1="4" x2="6" y2="8" />
                            <line x1="6" y1="12" x2="6" y2="20" />
                            <circle cx="12" cy="16" r="2" />
                            <line x1="12" y1="4" x2="12" y2="14" />
                            <line x1="12" y1="18" x2="12" y2="20" />
                            <circle cx="18" cy="7" r="2" />
                            <line x1="18" y1="4" x2="18" y2="5" />
                            <line x1="18" y1="9" x2="18" y2="20" />
                        </svg>
                        Assign Plan
                    </a>
                    <?php
                } else {
                    // Show subscription link first for free plan users
                    if ($isFreePlan): ?>
                        <a class="nav-item nav-link <?= (basename($_SERVER['PHP_SELF']) == 'subscribe.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>subscriptions/subscribe.php">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-credit-card me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z"></path>
                                <path d="M3 10l18 0"></path>
                                <path d="M7 15l.01 0"></path>
                                <path d="M11 15l2 0"></path>
                            </svg>
                            Subscription Plans
                        </a>
                    <?php endif; ?>

                    <!-- Navigation Links with Active Check -->
                    <a class="nav-item nav-link <?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>dashboard.php">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-dashboard me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <circle cx="12" cy="13" r="2" />
                            <path d="M13.45 11.55l2.05 -2.05" />
                            <path d="M6.4 20a9 9 0 1 1 11.2 0z" />
                        </svg>
                        Dashboard
                    </a>
                    <a class="nav-item nav-link <?= (basename($_SERVER['PHP_SELF']) == 'campaign_management.php' || basename($_SERVER['PHP_SELF']) == 'backlink_management.php' || basename($_SERVER['PHP_SELF']) == 'bulk_upload_backlinks.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>campaigns/campaign_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-campaign me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 3h18v18h-18z" />
                            <path d="M9 9h6v6h-6z" />
                        </svg>
                        Campaign Management
                    </a>

                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a class="nav-item nav-link <?= ((basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'form.php') && strpos($_SERVER['REQUEST_URI'], 'users') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>users/index.php">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                            </svg>
                            User Management
                        </a>
                    <?php endif; ?>

                    <?php if (!$isFreePlan): ?>
                        <a class="nav-item nav-link <?= (basename($_SERVER['PHP_SELF']) == 'subscribe.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>subscriptions/subscribe.php">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-credit-card me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z"></path>
                                <path d="M3 10l18 0"></path>
                                <path d="M7 15l.01 0"></path>
                                <path d="M11 15l2 0"></path>
                            </svg>
                            Subscription Plans
                        </a>
                    <?php endif; ?>
                <?php } ?>

                <a class="nav-item nav-link <?= (basename($_SERVER['PHP_SELF']) == 'logout.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>logout.php">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-logout me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
                        <path d="M7 12h14l-3 -3m0 6l3 -3" />
                    </svg>
                    Logout
                </a>
                <!-- Profile Link (Special Styling, Rightmost) -->
                <a class="nav-item nav-link ms-3 <?= (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>users/profile.php" style="background-color: #f1f5f9; border-radius: 50px; padding: 8px 16px;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <circle cx="12" cy="7" r="4" />
                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                    </svg>
                    <span class="d-none d-md-inline">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                </a>
            </div>
        </div>
    </div>
</nav>