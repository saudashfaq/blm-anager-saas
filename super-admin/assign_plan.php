<?php
require_once __DIR__ . '/superadmin_middleware.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../subscriptions/config/stripe.php';
require_once __DIR__ . '/../config/subscription_plans.php';


function assignPlanToCompany($companyId, $planName, $pdo)
{
    try {
        $pdo->beginTransaction();

        // Validate plan name
        if (!array_key_exists($planName, SUBSCRIPTION_LIMITS)) {
            throw new Exception("Invalid plan name: $planName");
        }

        $planDetails = SUBSCRIPTION_LIMITS[$planName];
        $newPlanId = getStripePlanId($planName);

        // Get current subscription details for logging
        $stmt = $pdo->prepare("
            SELECT plan_name, plan_id, status 
            FROM company_subscriptions 
            WHERE company_id = ?
        ");
        $stmt->execute([$companyId]);
        $currentSubscription = $stmt->fetch(PDO::FETCH_ASSOC);

        $oldPlanName = $currentSubscription['plan_name'] ?? null;
        $oldPlanId = $currentSubscription['plan_id'] ?? null;

        // Check if subscription exists
        if ($currentSubscription) {
            // Update existing subscription
            $stmt = $pdo->prepare("
                UPDATE company_subscriptions 
                SET plan_id = :plan_id,
                    plan_name = :plan_name,
                    status = 'active',
                    current_period_start = CURRENT_TIMESTAMP,
                    current_period_end = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 YEAR),
                    next_billing_date = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 YEAR),
                    session_id = 'manual_assignment',
                    verified_at = NOW(),
                    cancelled_at = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE company_id = :company_id
            ");

            $stmt->execute([
                'plan_id' => $newPlanId,
                'plan_name' => $planName,
                'company_id' => $companyId
            ]);
        } else {
            // Insert new subscription (for companies without any subscription)
            $stmt = $pdo->prepare("
                INSERT INTO company_subscriptions (
                    company_id,
                    plan_id,
                    plan_name,
                    status,
                    current_period_start,
                    current_period_end,
                    next_billing_date,
                    session_id,
                    verified_at
                ) VALUES (
                    :company_id,
                    :plan_id,
                    :plan_name,
                    'active',
                    CURRENT_TIMESTAMP,
                    DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 YEAR),
                    DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 YEAR),
                    'manual_assignment',
                    NOW()
                )
            ");

            $stmt->execute([
                'company_id' => $companyId,
                'plan_id' => $newPlanId,
                'plan_name' => $planName
            ]);
        }

        // Log the subscription change
        $stmt = $pdo->prepare("
            INSERT INTO subscription_change_logs (
                company_id,
                old_plan_name,
                new_plan_name,
                old_plan_id,
                new_plan_id,
                change_reason,
                changed_by_system
            ) VALUES (
                :company_id,
                :old_plan_name,
                :new_plan_name,
                :old_plan_id,
                :new_plan_id,
                'manual_assignment',
                'superadmin'
            )
        ");

        $stmt->execute([
            'company_id' => $companyId,
            'old_plan_name' => $oldPlanName,
            'new_plan_name' => $planName,
            'old_plan_id' => $oldPlanId,
            'new_plan_id' => $newPlanId
        ]);

        // Update company record
        $stmt = $pdo->prepare("
            UPDATE companies 
            SET subscription_plan = :plan_name,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :company_id
        ");

        $stmt->execute([
            'plan_name' => strtolower($planDetails['name']),
            'company_id' => $companyId
        ]);

        $pdo->commit();
        return ['success' => true, 'message' => 'Plan assigned successfully'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyId = $_POST['company_id'] ?? null;
    $planName = $_POST['plan_name'] ?? null;

    if ($companyId && $planName) {
        $result = assignPlanToCompany($companyId, $planName, $pdo);
        $message = $result['message'];
        $success = $result['success'];
    }
}

// Get only companies with free or basic plans
$stmt = $pdo->prepare("
    SELECT id, name, subscription_plan 
    FROM companies 
    WHERE subscription_plan IN ('free', 'basic') 
    ORDER BY name
");
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available plans
$plans = array_keys(SUBSCRIPTION_LIMITS);

$pageTitle = 'Assign Subscription Plan';
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Assign Subscription Plan</h3>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="mb-3">
                    <label for="company_id" class="form-label">Company</label>
                    <select name="company_id" id="company_id" class="form-select" required>
                        <option value="">Select Company</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>">
                                <?php echo htmlspecialchars($company['name']); ?>
                                (Current: <?php echo htmlspecialchars($company['subscription_plan']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="plan_name" class="form-label">Subscription Plan</label>
                    <select name="plan_name" id="plan_name" class="form-select" required>
                        <option value="">Select Plan</option>
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?php echo $plan; ?>">
                                <?php echo htmlspecialchars(SUBSCRIPTION_LIMITS[$plan]['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="alert alert-warning">
                    <h4 class="alert-heading">Warning!</h4>
                    <p>This will assign a subscription plan directly without going through Stripe. Use this only for testing or special cases.</p>
                </div>

                <button type="submit" class="btn btn-primary">Assign Plan</button>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>