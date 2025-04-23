<?php
require_once __DIR__ . '/../middleware.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/Pagination.php';

// Set page title and body class
$pageTitle = 'Backlink Management';
$bodyClass = 'theme-light';

// Ensure campaign_id is set and is a valid integer
if (empty($_GET['campaign_id']) || !ctype_digit($_GET['campaign_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

try {
    $campaignId = (int) $_GET['campaign_id']; // Convert to integer for security
    // Fetch campaign details in a single query (optimized)
    $stmt = $pdo->prepare("SELECT id, `name`, base_url FROM campaigns WHERE id = ? LIMIT 1");
    $stmt->execute([$campaignId]);
    $campaignData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($campaignData) {
        $campaignExists = true;
        $base_url = $campaignData['base_url'] ?? '';
    } else {
        header('Location:' . BASE_URL . 'campaigns/campaign_management.php?error=Campaign not found');
        exit();
    }

    $query = "SELECT 
    COUNT(*) AS total_backlinks,
    SUM(CASE WHEN status = 'alive' THEN 1 ELSE 0 END) AS alive_backlinks,
    SUM(CASE WHEN status = 'dead' THEN 1 ELSE 0 END) AS dead_backlinks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_backlinks,
    SUM(CASE WHEN is_duplicate = 'yes' THEN 1 ELSE 0 END) AS duplicate_backlinks
    FROM backlinks where campaign_id = :campaign_id";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['campaign_id' => $campaignId]);
    $result = $stmt->fetch();

    $totalBacklinks = $result['total_backlinks'];
    $activeBacklinks = $result['alive_backlinks'];
    $deadBacklinks = $result['dead_backlinks'];
    $duplicateBacklinks = $result['duplicate_backlinks'];

    // Pagination setup
    $itemsPerPage = 10; // Number of backlinks per page
    $currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

    // Apply filters from query parameters
    $filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : null;
    $filterValue = isset($_GET['filter_value']) ? $_GET['filter_value'] : null;

    // Get total number of backlinks for the campaign (considering filters)
    $countQuery = "SELECT COUNT(*) FROM backlinks WHERE campaign_id = :campaign_id";
    $countParams = ['campaign_id' => $campaignId];
    if ($filterType === 'status' && in_array($filterValue, ['alive', 'dead'])) {
        $countQuery .= " AND status = :status";
        $countParams['status'] = $filterValue;
    } elseif ($filterType === 'duplicate' && $filterValue === 'yes') {
        $countQuery .= " AND is_duplicate = :is_duplicate";
        $countParams['is_duplicate'] = 'yes';
    }
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalFilteredBacklinks = $countStmt->fetchColumn();

    // Initialize Pagination class
    $pagination = new Pagination($totalFilteredBacklinks, $itemsPerPage, $currentPage, '');

    // Fetch backlinks with pagination and filters
    $offset = $pagination->getOffset();
    $backlinkQuery = "SELECT b.*, c.name AS campaign_name, c.base_url, 
        u.username AS created_by_username FROM backlinks b 
        JOIN campaigns c ON b.campaign_id = c.id
        JOIN users u ON b.created_by = u.id
        WHERE (b.campaign_id = :campaign_id)";
    $params = ['campaign_id' => $campaignId];
    if ($filterType === 'status' && in_array($filterValue, ['alive', 'dead'])) {
        $backlinkQuery .= " AND b.status = :status";
        $params['status'] = $filterValue;
    } elseif ($filterType === 'duplicate' && $filterValue === 'yes') {
        $backlinkQuery .= " AND b.is_duplicate = :is_duplicate";
        $params['is_duplicate'] = 'yes';
    }
    $backlinkQuery .= " ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($backlinkQuery);
    $stmt->bindValue(':campaign_id', $campaignId, PDO::PARAM_INT);
    if ($filterType === 'status' && in_array($filterValue, ['alive', 'dead'])) {
        $stmt->bindValue(':status', $filterValue, PDO::PARAM_STR);
    } elseif ($filterType === 'duplicate' && $filterValue === 'yes') {
        $stmt->bindValue(':is_duplicate', 'yes', PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $backlinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die('Database error occurred');
}

$stmt = $pdo->prepare("SELECT id, `name` FROM campaigns");
$stmt->execute();
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container-xl">
        <!-- Alerts container -->
        <div id="alerts-container" class="mt-3"></div>

        <div class="page-header d-print-none mb-3">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                                        <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
                                    </svg>
                        Backlink Management: <?= htmlspecialchars($campaignData['name']) ?>
                    </h2>
                    <div class="text-muted mt-1">
                        <a href="<?= htmlspecialchars($campaignData['base_url']) ?>" target="_blank" class="text-reset">
                            <?= htmlspecialchars($campaignData['base_url']) ?>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-external-link ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" />
                                <path d="M11 13l9 -9" />
                                <path d="M15 4h5v5" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a href="campaign_management.php" class="btn btn-outline-secondary d-none d-sm-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-left" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12l14 0" />
                                <path d="M5 12l6 6" />
                                <path d="M5 12l6 -6" />
                                                </svg>
                            Back to Campaigns
                        </a>
                        <a href="bulk_upload_backlinks.php?campaign_id=<?= $campaignId ?>" class="btn btn-outline-primary d-none d-sm-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-upload" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                <path d="M12 11v6" />
                                <path d="M9.5 13.5l2.5 -2.5l2.5 2.5" />
                                                </svg>
                            Bulk Upload
                        </a>
                        <button class="btn btn-primary d-inline-block" data-bs-toggle="modal" data-bs-target="#add-backlink-modal">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                                                </svg>
                            Add Backlink
                        </button>
                    </div>
                </div>
            </div>
                    </div>
                </div>
            </div>

            <div class="modal modal-blur fade" id="add-backlink-modal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link-plus me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M9 15l6 -6" />
                                    <path d="M11 6l.463 -.536a5 5 0 0 1 7.071 7.072l-.534 .463" />
                                    <path d="M13 18l-.397 .534a5.068 5.068 0 0 1 -7.127 0a4.972 4.972 0 0 1 0 -7.071l.531 -.463" />
                                    <path d="M20 17v5" />
                                    <path d="M22 19h-5" />
                                </svg>
                                Add New Backlink
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="add-backlink-form" action="backlink_management_crud.php" method="post">
                            <div class="modal-body">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="campaign_id" id="hidden-campaign-id" value="<?= !empty($campaignId) ? $campaignId : '' ?>">
                                <input type="hidden" name="campaign_base_url" id="hidden-campaign-url" value="<?= !empty($base_url) ? $base_url : '' ?>">
                                <div class="mb-3">
                                    <label class="form-label required">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                                            <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
                                        </svg>
                                        Backlink <small>(Webpage Link that contains link back to your website)</small>
                                    </label>
                                    <input type="url" name="backlink_url" class="form-control" required maxlength="255">
                                    <span class="error-message" style="color: red;"></span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-target me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <circle cx="12" cy="12" r="9" />
                                            <circle cx="12" cy="12" r="6" />
                                            <circle cx="12" cy="12" r="3" />
                                            <path d="M12 3v18" />
                                            <path d="M3 12h18" />
                                        </svg>
                                        Target <small>(Your Website Link of Post/Page)</small>
                                    </label>
                                    <input type="text" name="target_url" class="form-control" maxlength="255">
                                    <span class="error-message" style="color: red;"></span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-anchor me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M12 21v-9m-3 0h6m-6 0a2 2 0 1 1 -4 0a2 2 0 1 1 4 0" />
                                            <path d="M4 8v-2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v2" />
                                            <path d="M4 16l8 5l8 -5" />
                                        </svg>
                                        Anchor Text
                                    </label>
                                    <input type="text" name="anchor_text" class="form-control" maxlength="255">
                                    <span class="error-message" style="color: red;"></span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M18 6l-12 12" />
                                        <path d="M6 6l12 12" />
                                    </svg>
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M5 12l5 5l10 -10" />
                                    </svg>
                                    Add Backlink
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                            <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
                        </svg>
                        Backlinks
                    </h3>
                    <button id="bulk-delete-btn" class="btn btn-danger" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M4 7h16" />
                            <path d="M10 11v6" />
                            <path d="M14 11v6" />
                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                        </svg>
                        Delete Selected
                    </button>
                </div>
                <div class="card-body backlinks-card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                                            <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
                                        </svg>
                                        Backlink
                                    </th>
                                    <th>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-anchor me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M12 21v-9m-3 0h6m-6 0a2 2 0 1 1 -4 0a2 2 0 1 1 4 0" />
                                            <path d="M4 8v-2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v2" />
                                            <path d="M4 16l8 5l8 -5" />
                                        </svg>
                                        Anchor
                                    </th>
                                    <th>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-target me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <circle cx="12" cy="12" r="9" />
                                            <circle cx="12" cy="12" r="6" />
                                            <circle cx="12" cy="12" r="3" />
                                            <path d="M12 3v18" />
                                            <path d="M3 12h18" />
                                        </svg>
                                        ReferringLink
                                    </th>
                                    <th>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-status-change me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M6 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                            <path d="M18 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                            <path d="M6 16v-4a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v4" />
                                            <path d="M6 8v-2" />
                                            <path d="M18 16v2" />
                                        </svg>
                                        Status
                                    </th>
                                    <th>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clock me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M12 7v5l3 3" />
                                        </svg>
                                        Created At
                                    </th>
                                    <th>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-settings me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backlinks as $backlink): ?>
                                    <tr data-id="<?= htmlspecialchars($backlink['id']) ?>">
                                        <td><input type="checkbox" class="backlink-select" value="<?= htmlspecialchars($backlink['id']) ?>"></td>
                                        <td>
                                            <?php if ($backlink['is_duplicate'] === 'yes'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-copy duplicate-icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M8 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" />
                                                    <path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2" />
                                                </svg>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($backlink['backlink_url']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($backlink['anchor_text']) ?></td>
                                        <td><?= htmlspecialchars($backlink['target_url']) ?></td>
                                        <td><span class="badge bg-<?= match ($backlink['status']) {
                                                                        'alive' => 'success',
                                                                        'dead' => 'danger',
                                                                        default => 'warning'
                                                                    } ?>"><?= htmlspecialchars($backlink['status']) ?></span></td>
                                        <td><?= date('Y-m-d H:i', strtotime($backlink['created_at'])) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger delete-single" data-id="<?= htmlspecialchars($backlink['id']) ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 7h16" />
                                                    <path d="M10 11v6" />
                                                    <path d="M14 11v6" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <?= $pagination->render(); ?>
            </div>
        </div>
    </div>

<!-- Scripts for backlink management will be included via the footer.php and page-specific script -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Include backlink management specific JavaScript -->
<script src="<?= defined('BASE_URL') ? BASE_URL : '/' ?>includes/js/backlink-management.js"></script>