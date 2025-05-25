<?php
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/users/company_helper.php';

$pageTitle = 'Dashboard';
$bodyClass = 'theme-light';

try {
    $company_id = get_current_company_id();

    // Total campaigns
    $totalCampaignsStmt = $pdo->prepare("SELECT COUNT(*) FROM campaigns WHERE company_id = ?");
    $totalCampaignsStmt->execute([$company_id]);
    $totalCampaigns = $totalCampaignsStmt->fetchColumn();

    // Total backlinks
    $totalBacklinksStmt = $pdo->prepare("
        SELECT COUNT(*) FROM backlinks b
        JOIN campaigns c ON b.campaign_id = c.id
        WHERE c.company_id = ?
    ");
    $totalBacklinksStmt->execute([$company_id]);
    $totalBacklinks = $totalBacklinksStmt->fetchColumn();

    // Active/Inactive campaigns
    $campaignsStmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM campaigns WHERE company_id = ? GROUP BY status");
    $campaignsStmt->execute([$company_id]);
    $campaignsStats = [];
    while ($row = $campaignsStmt->fetch(PDO::FETCH_ASSOC)) {
        $campaignsStats[$row['status']] = $row['count'];
    }
    $activeCampaigns = $campaignsStats['enabled'] ?? 0;
    $inactiveCampaigns = $campaignsStats['disabled'] ?? 0;

    // Alive/Dead backlinks
    $backlinksStmt = $pdo->prepare("
        SELECT b.status, COUNT(*) as count 
        FROM backlinks b
        JOIN campaigns c ON b.campaign_id = c.id
        WHERE c.company_id = ?
        GROUP BY b.status
    ");
    $backlinksStmt->execute([$company_id]);
    $backlinksStats = [];
    while ($row = $backlinksStmt->fetch(PDO::FETCH_ASSOC)) {
        $backlinksStats[$row['status']] = $row['count'];
    }
    $aliveBacklinks = $backlinksStats['alive'] ?? 0;
    $deadBacklinks = $backlinksStats['dead'] ?? 0;
    $pendingBacklinks = $backlinksStats['pending'] ?? 0;
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die('Database error occurred');
}

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<!-- Custom CSS for dashboard -->
<style>
    .welcome-message {
        text-align: center;
        padding: 2rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 0.75rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .welcome-message h2 {
        color: #206bc4;
        font-size: 2.25rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .welcome-message p {
        color: #495057;
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }

    .welcome-message .btn {
        background-color: #206bc4;
        border: none;
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        font-weight: 500;
        transition: background-color 0.3s ease;
    }

    .welcome-message .btn:hover {
        background-color: #1a5aa3;
    }

    .stats-card {
        transition: all 0.3s ease;
        border: none;
        border-radius: 0.75rem;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .stats-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .stats-card .card-body {
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stats-card .icon-container {
        flex-shrink: 0;
    }

    .stats-card .icon-container svg {
        width: 40px;
        height: 40px;
        color: #206bc4;
    }

    .stats-card .card-title {
        color: #6c757d;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .stats-card .card-value {
        color: #212529;
        font-size: 1.75rem;
        font-weight: 700;
    }

    .chart-container {
        position: relative;
        margin-top: 2rem;
        padding: 1.5rem;
        background: #ffffff;
        border-radius: 0.75rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .chart-container canvas {
        max-height: 300px;
    }

    .no-data-message {
        text-align: center;
        color: #6c757d;
        padding: 2rem;
        font-size: 1.1rem;
    }
</style>

<div class="container mt-4">
    <?php if ($totalCampaigns == 0): ?>
        <div class="welcome-message mt-4">
            <h2>Welcome to Your Backlink Manager!</h2>
            <p>It looks like you haven't created any campaigns yet. Start by creating your first campaign to manage your backlinks effectively.</p>
            <a href="<?= BASE_URL ?>campaigns/campaign_management.php" class="btn btn-primary">Create Your First Campaign</a>
        </div>
    <?php elseif ($totalBacklinks == 0): ?>
        <div class="row">
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-campaign" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M3 3h18v18h-18z" />
                                <path d="M9 9h6v6h-6z" />
                            </svg>
                        </div>
                        <div>
                            <div class="card-title">Total Campaigns</div>
                            <div class="card-value"><?= $totalCampaigns ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12l5 5l10 -10" />
                            </svg>
                        </div>
                        <div>
                            <div class="card-title">Active Campaigns</div>
                            <div class="card-value"><?= $activeCampaigns ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="welcome-message mt-4">
            <h2>Add Your First Backlink</h2>
            <p>You've created a campaign, but you haven't added any backlinks yet. Start tracking your first backlink now!</p>
            <a href="<?= BASE_URL ?>campaigns/campaign_management.php" class="btn btn-primary">Add Your First Backlink</a>
        </div>
    <?php else: ?>
        <!-- Dashboard with all stats when data is available -->
        <div class="row row-deck">
            <div class="col-md-6 col-lg-3">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-campaign" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M3 3h18v18h-18z" />
                                <path d="M9 9h6v6h-6z" />
                            </svg>
                        </div>
                        <div>
                            <div class="card-title">Total Campaigns</div>
                            <div class="card-value"><?= $totalCampaigns ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5" />
                                <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5" />
                            </svg>
                        </div>
                        <div>
                            <div class="card-title">Total Backlinks</div>
                            <div class="card-value"><?= $totalBacklinks ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12l5 5l10 -10" />
                            </svg>
                        </div>
                        <div>
                            <div class="card-title">Active Campaigns</div>
                            <div class="card-value"><?= $activeCampaigns ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link-off" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M9 15l3 -3m2 -2l1 -1" />
                                <path d="M11 6l4 -4a3.5 3.5 0 1 1 5 5l-4 4" />
                                <path d="M3 3l18 18" />
                                <path d="M13 18l-4 4a3.5 3.5 0 1 1 -5 -5l4 -4" />
                            </svg>
                        </div>
                        <div>
                            <div class="card-title">Dead Backlinks</div>
                            <div class="card-value"><?= $deadBacklinks ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <canvas id="campaignsChart" data-active="<?= $activeCampaigns ?>" data-inactive="<?= $inactiveCampaigns ?>"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <canvas id="backlinksChart" data-alive="<?= $aliveBacklinks ?>" data-dead="<?= $deadBacklinks ?>" data-pending="<?= $pendingBacklinks ?>"></canvas>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Load Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Include dashboard specific JavaScript -->
<script src="<?= defined('BASE_URL') ? BASE_URL : '/' ?>includes/js/dashboard.js"></script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>