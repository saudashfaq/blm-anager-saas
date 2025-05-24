<?php
// campaign_management.php
require_once __DIR__ . '/../middleware.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

// Set page title and body class
$pageTitle = 'Campaign Manager';
$bodyClass = 'theme-light';

// Get campaigns with backlink counts, sorted by created_at DESC (latest first)
$company_id = $_SESSION['company_id'];
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        COUNT(DISTINCT b.id) as total_backlinks,
        SUM(CASE WHEN b.status = 'alive' THEN 1 ELSE 0 END) as alive_backlinks,
        SUM(CASE WHEN b.status = 'dead' THEN 1 ELSE 0 END) as dead_backlinks,
        SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_backlinks
    FROM campaigns c
    LEFT JOIN backlinks b ON c.id = b.campaign_id
    WHERE c.company_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute([$company_id]);
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
                        <div class="card card-stacked">
                            <div class="status-badge position-absolute end-0 top-0 mt-2 me-2">
                                <span class="badge bg-<?= $campaign['status'] === 'enabled' ? 'green-lt' : 'red-lt' ?>">
                                    <?= ucfirst($campaign['status']) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h3 class="card-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-building-store me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M3 21l18 0" />
                                        <path d="M3 7v1a3 3 0 0 0 6 0v-1m4 0v1a3 3 0 0 0 6 0v-1" />
                                        <path d="M5 21v-12a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v12" />
                                    </svg>
                                    <?= htmlspecialchars($campaign['name']) ?>
                                </h3>

                                <div class="mt-3 mb-3">
                                    <div class="datagrid">
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Base URL</div>
                                            <div class="datagrid-content">
                                                <a href="<?= htmlspecialchars($campaign['base_url']) ?>" target="_blank" class="text-reset d-inline-flex">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                                                        <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
                                                    </svg>
                                                    <?= htmlspecialchars($campaign['base_url']) ?>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Verification</div>
                                            <div class="datagrid-content">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clock me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <circle cx="12" cy="12" r="9" />
                                                    <path d="M12 7v5l3 3" />
                                                </svg>
                                                <span class="verification-frequency"><?= htmlspecialchars(ucfirst($campaign['verification_frequency'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Backlinks Stats -->
                                <div class="card card-sm mb-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-primary text-white avatar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5"></path>
                                                        <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <div class="font-weight-medium">Backlinks</div>
                                                <div class="text-muted">
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-green-lt me-2"><?= (int)$campaign['alive_backlinks'] ?> Alive</span>
                                                        <span class="badge bg-red-lt me-2"><?= (int)$campaign['dead_backlinks'] ?> Dead</span>
                                                        <span class="badge bg-yellow-lt"><?= (int)$campaign['pending_backlinks'] ?> Pending</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="h1 mb-0"><?= (int)$campaign['total_backlinks'] ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div class="btn-list">
                                        <a href="backlink_management.php?campaign_id=<?= $campaign['id'] ?>" class="btn btn-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
                                                <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path>
                                            </svg>
                                            View
                                        </a>
                                        <a href="bulk_upload_backlinks.php?campaign_id=<?= $campaign['id'] ?>" class="btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-upload" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                                                <path d="M7 9l5 -5l5 5"></path>
                                                <path d="M12 4l0 12"></path>
                                            </svg>
                                            Upload
                                        </a>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-settings" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"></path>
                                                <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                            </svg>
                                            Actions
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit-campaign-modal" data-edit-campaign data-campaign-id="<?= $campaign['id'] ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                                                    <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                                                    <path d="M16 5l3 3" />
                                                </svg>
                                                Edit Campaign
                                            </a>
                                            <a class="dropdown-item" href="export_campaign_report.php?campaign_id=<?= $campaign['id'] ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-export me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                    <path d="M11.5 21h-4.5a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v5m-5 6h7m-3 -3l3 3l-3 3" />
                                                </svg>
                                                Export Report
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#delete-campaign-modal" data-delete-campaign data-campaign-id="<?= $campaign['id'] ?>" data-campaign-name="<?= htmlspecialchars($campaign['name']) ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M4 7l16 0"></path>
                                                    <path d="M10 11l0 6"></path>
                                                    <path d="M14 11l0 6"></path>
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                </svg>
                                                Delete
                                            </a>
                                        </div>
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
                    <input type="hidden" name="action" value="create_campaign">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <label for="campaign_name" class="form-label required">Name</label>
                        <input type="text" class="form-control" id="campaign_name" name="campaign_name" required>
                        <span id="campaign_name-error" class="error-message text-danger"></span>
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
                            <option value="weekly" selected>Weekly</option>
                            <option value="every_two_weeks">Every Two Weeks</option>
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
                    <input type="hidden" name="campaign_id" id="edit-campaign-id">

                    <div class="mb-3">
                        <label for="edit-name" class="form-label required">Name</label>
                        <input type="text" class="form-control" id="edit-name" name="campaign_name" required>
                        <span id="edit-campaign_name-error" class="error-message text-danger"></span>
                    </div>

                    <div class="mb-3">
                        <label for="edit-base-url" class="form-label">Base URL</label>
                        <input type="url" class="form-control" id="edit-base-url" disabled>
                        <div class="form-text">Base URL cannot be changed after campaign creation</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-verification-frequency" class="form-label required">Verification Frequency</label>
                        <select class="form-select" id="edit-verification-frequency" name="verification_frequency" required>
                            <option value="weekly">Weekly</option>
                            <option value="every_two_weeks">Every Two Weeks</option>
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

<!-- Delete Campaign Modal -->
<div class="modal modal-blur fade" id="delete-campaign-modal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title text-center">Are you sure?</div>
                <div class="text-center mt-2">
                    <p>You're about to delete campaign: <strong id="delete-campaign-name"></strong></p>
                    <p class="text-muted">This action cannot be undone. All backlinks associated with this campaign will also be deleted.</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">
                                Cancel
                            </button>
                        </div>
                        <div class="col">
                            <form id="delete-campaign-form" method="POST">
                                <input type="hidden" name="action" value="delete_campaign">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="campaign_id" id="delete-campaign-id">
                                <button type="submit" class="btn btn-danger w-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M4 7l16 0"></path>
                                        <path d="M10 11l0 6"></path>
                                        <path d="M14 11l0 6"></path>
                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts for campaign management will be included via the footer.php and page-specific script -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Include campaign management specific JavaScript -->
<script src="<?= defined('BASE_URL') ? BASE_URL : '/' ?>includes/js/campaign-management.js"></script>