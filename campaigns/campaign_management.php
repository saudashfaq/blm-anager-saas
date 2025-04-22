<?php
// campaign_management.php
require_once __DIR__ . '/../middleware.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

// Set page title and body class
$pageTitle = 'Campaign Manager';
$bodyClass = 'theme-light';

// Get campaigns with backlink counts, sorted by created_at DESC (latest first)
$stmt = $pdo->query("
    SELECT 
        c.*,
        COUNT(DISTINCT b.id) as total_backlinks,
        SUM(CASE WHEN b.status = 'alive' THEN 1 ELSE 0 END) as alive_backlinks,
        SUM(CASE WHEN b.status = 'dead' THEN 1 ELSE 0 END) as dead_backlinks,
        SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_backlinks
    FROM campaigns c
    LEFT JOIN backlinks b ON c.id = b.campaign_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-building-store me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 21l18 0" />
                            <path d="M3 7v1a3 3 0 0 0 6 0v-1m4 0v1a3 3 0 0 0 6 0v-1" />
                            <path d="M5 21v-12a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v12" />
                            <path d="M10 14v-2a2 2 0 1 1 4 0v2" />
                            <path d="M10 21v-4a2 2 0 1 1 4 0v4" />
                        </svg>
                        Campaign Manager
                    </h2>
                </div>
                <div class="col-auto ms-auto">
                    <button class="btn btn-ghost-primary" data-bs-toggle="modal" data-bs-target="#add-campaign-modal">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 5l0 14" />
                            <path d="M5 12l14 0" />
                        </svg>
                        Add New Campaign
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <!-- Alerts container -->
            <div id="alerts-container"></div>

            <!-- Filter and Search Options -->
            <div class="row mb-4 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <circle cx="10" cy="10" r="7" />
                                <path d="M21 21l-6 -6" />
                            </svg>
                        </span>
                        <input type="text" id="search-campaign" class="form-control" placeholder="Search by name or URL">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sort-ascending-letters" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M15 10v-5h-3l3 -3l3 3h-3v5z" />
                                <path d="M15 14v7h3l-3 3l-3 -3h3v-7z" />
                                <path d="M4 15h7" />
                                <path d="M4 9h6" />
                                <path d="M4 4h5" />
                            </svg>
                        </span>
                        <select id="sort-campaigns" class="form-select">
                            <option value="name-asc">Alphabetically (A-Z)</option>
                            <option value="name-desc">Alphabetically (Z-A)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-filter" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-8.5l-4.48 -4.928a2 2 0 0 1 -.52 -1.345v-2.227z" />
                            </svg>
                        </span>
                        <select id="filter-status" class="form-select">
                            <option value="all">All Campaigns</option>
                            <option value="enabled">Enabled</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row row-cards" id="campaigns-container">
                <!-- Display message if no campaigns are found -->
                <?php if (empty($campaigns)): ?>
                    <div class="col-12 text-center">
                        <div class="empty">
                            <div class="empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder-off" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M3 3l18 18" />
                                    <path d="M19 7h-8.5l-4.015 -4.015a2 2 0 0 0 -1.985 -.985h-2.5a2 2 0 0 0 -2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2 -2v-10" />
                                </svg>
                            </div>
                            <p class="empty-title">No campaigns found</p>
                            <p class="empty-subtitle text-muted">
                                It looks like you haven't created any campaigns yet. Let's get started!
                            </p>
                            <div class="empty-action">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-campaign-modal">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M12 5l0 14" />
                                        <path d="M5 12l14 0" />
                                    </svg>
                                    Create Your First Campaign
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($campaigns as $campaign): ?>
                    <div class="col-md-6 col-lg-4 campaign-card" data-name="<?= htmlspecialchars(strtolower($campaign['name'])) ?>" data-base-url="<?= htmlspecialchars(strtolower($campaign['base_url'])) ?>" data-status="<?= htmlspecialchars($campaign['status']) ?>" data-created-at="<?= htmlspecialchars($campaign['created_at']) ?>">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?= htmlspecialchars($campaign['name']) ?></h3>
                                <span class="badge ms-auto bg-<?= $campaign['status'] === 'enabled' ? 'green-lt' : 'red-lt' ?>">
                                    <?= ucfirst($campaign['status']) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <p>
                                    <strong>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                                            <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
                                        </svg>
                                        Base URL:
                                    </strong>
                                    <?= htmlspecialchars($campaign['base_url']) ?>
                                </p>
                                <p>
                                    <strong>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clock me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M12 7v5l3 3" />
                                        </svg>
                                        Verification:
                                    </strong>
                                    <span class="verification-frequency"> <?= htmlspecialchars(ucfirst($campaign['verification_frequency'])) ?></span>
                                </p>

                                <div class="mt-3">
                                    <div class="row g-2 align-items-center">
                                        <div class="col">
                                            <div class="text-muted">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chart-bar me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M3 12h4v8h-4z" />
                                                    <path d="M10 8h4v12h-4z" />
                                                    <path d="M17 4h4v16h-4z" />
                                                    <path d="M3 20h18" />
                                                </svg>
                                                Backlinks: <strong><?= (int)$campaign['total_backlinks'] ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-auto" data-bs-toggle="tooltip" data-bs-placement="top" title="View Backlinks">
                                            <a href="backlink_management.php?campaign_id=<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-external-link" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" />
                                                    <path d="M11 13l9 -9" />
                                                    <path d="M15 4h5v5" />
                                                </svg>
                                                View
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex mt-4 justify-content-between">
                                    <a href="export_campaign_report.php?id=<?= $campaign['id'] ?>" class="btn btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Export report">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-export" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                            <path d="M11.5 21h-4.5a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v5m-5 6h7m-3 -3l3 3l-3 3" />
                                        </svg>
                                        Export
                                    </a>

                                    <div>
                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#edit-campaign-modal" data-edit-campaign data-campaign-id="<?= $campaign['id'] ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                                                <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                                                <path d="M16 5l3 3" />
                                            </svg>
                                            Edit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Campaign Modal -->
<div class="modal" id="add-campaign-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="campaign-form" method="POST">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label required">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <span id="name-error" class="error-message text-danger"></span>
                    </div>

                    <div class="mb-3">
                        <label for="base-url" class="form-label required">Base URL</label>
                        <input type="url" class="form-control" id="base-url" name="base_url" placeholder="https://example.com" required>
                        <span id="base_url-error" class="error-message text-danger"></span>
                        <div class="form-text">The main domain you're building backlinks for</div>
                    </div>

                    <div class="mb-3">
                        <label for="verification-frequency" class="form-label required">Verification Frequency</label>
                        <select class="form-select" id="verification-frequency" name="verification_frequency" required>
                            <option value="daily">Daily</option>
                            <option value="weekly" selected>Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        <span id="verification_frequency-error" class="error-message text-danger"></span>
                        <div class="form-text">How often backlinks will be verified</div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label required">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="enabled" selected>Enabled</option>
                            <option value="disabled">Disabled</option>
                        </select>
                        <span id="status-error" class="error-message text-danger"></span>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Add Campaign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Campaign Modal -->
<div class="modal" id="edit-campaign-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="campaign-edit-form" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id" id="edit-campaign-id">

                    <div class="mb-3">
                        <label for="edit-name" class="form-label required">Name</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                        <span id="edit-name-error" class="error-message text-danger"></span>
                    </div>

                    <div class="mb-3">
                        <label for="edit-base-url" class="form-label required">Base URL</label>
                        <input type="url" class="form-control" id="edit-base-url" name="base_url" placeholder="https://example.com" required>
                        <span id="edit-base_url-error" class="error-message text-danger"></span>
                        <div class="form-text">The main domain you're building backlinks for</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-verification-frequency" class="form-label required">Verification Frequency</label>
                        <select class="form-select" id="edit-verification-frequency" name="verification_frequency" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        <span id="edit-verification_frequency-error" class="error-message text-danger"></span>
                        <div class="form-text">How often backlinks will be verified</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-status" class="form-label required">Status</label>
                        <select class="form-select" id="edit-status" name="status" required>
                            <option value="enabled">Enabled</option>
                            <option value="disabled">Disabled</option>
                        </select>
                        <span id="edit-status-error" class="error-message text-danger"></span>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include campaign management specific JavaScript -->
<script src="<?= defined('BASE_URL') ? BASE_URL : '/' ?>includes/js/campaign-management.js"></script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>