<?php
require_once __DIR__ . '/superadmin_middleware.php';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM company_subscriptions cs
    JOIN companies c ON cs.company_id = c.id
    JOIN users u ON c.id = u.company_id AND u.role = 'admin'
");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get subscribers with company and user details
$stmt = $pdo->prepare("
    SELECT 
        cs.*,
        c.name as company_name,
        u.id as user_id,
        u.username as username,
        u.email as user_email
    FROM company_subscriptions cs
    JOIN companies c ON cs.company_id = c.id
    JOIN users u ON c.id = u.company_id AND u.role = 'admin'
    ORDER BY cs.created_at DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Recent Subscribers';
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Subscribers</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Admin User</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Subscription ID</th>
                            <th>Next Billing</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $sub): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($sub['company_name']) ?>
                                    <br>
                                    <small class="text-muted">ID: <?= $sub['company_id'] ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($sub['username']) ?>
                                    <br>
                                    <small class="text-muted">ID: <?= $sub['user_id'] ?></small>
                                </td>
                                <td>
                                    <?php
                                    $planClass = match (strtolower($sub['plan_name'])) {
                                        'basic' => 'bg-blue-lt text-blue-darker',
                                        'premium' => 'bg-purple-lt text-purple-darker',
                                        'professional' => 'bg-indigo-lt text-indigo-darker',
                                        'enterprise' => 'bg-green-lt text-green-darker',
                                        default => 'bg-gray-lt text-gray-darker'
                                    };
                                    ?>
                                    <span class="badge <?= $planClass ?>" style="font-size: 0.875rem; padding: 0.3rem 0.6rem;">
                                        <?= htmlspecialchars(ucfirst($sub['plan_name'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match (strtolower($sub['status'])) {
                                        'active' => 'bg-green-lt text-green-darker',
                                        'cancelled' => 'bg-red-lt text-red-darker',
                                        'past_due' => 'bg-orange-lt text-orange-darker',
                                        'incomplete' => 'bg-yellow-lt text-yellow-darker',
                                        'incomplete_expired' => 'bg-red-lt text-red-darker',
                                        default => 'bg-gray-lt text-gray-darker'
                                    };

                                    $statusIcon = match (strtolower($sub['status'])) {
                                        'active' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M5 12l5 5l10 -10"></path>
                                        </svg>',
                                        'cancelled' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M18 6l-12 12"></path>
                                            <path d="M6 6l12 12"></path>
                                        </svg>',
                                        'past_due' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-alert-triangle me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M12 9v4"></path>
                                            <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"></path>
                                            <path d="M12 16h.01"></path>
                                        </svg>',
                                        'incomplete' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clock me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                            <path d="M12 7v5l3 3"></path>
                                        </svg>',
                                        'incomplete_expired' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clock-x me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M20.926 13.15a9 9 0 1 0 -7.835 7.784"></path>
                                            <path d="M12 7v5l2 2"></path>
                                            <path d="M22 22l-5 -5"></path>
                                            <path d="M17 22l5 -5"></path>
                                        </svg>',
                                        default => ''
                                    };
                                    ?>
                                    <span class="badge <?= $statusClass ?>" style="font-size: 0.875rem; padding: 0.3rem 0.6rem;">
                                        <span class="d-inline-flex align-items-center">
                                            <?= $statusIcon ?>
                                            <?= htmlspecialchars(str_replace('_', ' ', ucfirst($sub['status']))) ?>
                                        </span>
                                    </span>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($sub['stripe_subscription_id']) ?></small>
                                </td>
                                <td>
                                    <?php
                                    if ($sub['next_billing_date']) {
                                        // Check if it's already a timestamp
                                        if (is_numeric($sub['next_billing_date'])) {
                                            echo date('Y-m-d', $sub['next_billing_date']);
                                        } else {
                                            // If it's a date string, convert to timestamp first
                                            echo date('Y-m-d', strtotime($sub['next_billing_date']));
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($sub['created_at']) {
                                        echo date('Y-m-d H:i', strtotime($sub['created_at']));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= ($page - 1) ?>">&laquo; Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= ($page + 1) ?>">Next &raquo;</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>