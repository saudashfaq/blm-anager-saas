<?php
// subscribe.php
require_once __DIR__ . '/../middleware.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/config/stripe.php';
require_once __DIR__ . '/classes/StripeManager.php';
require_once __DIR__ . '/classes/SubscriptionVerifier.php';
require_once __DIR__ . '/classes/SubscriptionSessionManager.php';
require_once __DIR__ . '/../config/subscription_plans.php';


// Initialize Stripe manager
$stripeManager = new StripeManager();

// Get the current domain and protocol
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
$currentPath = $_SERVER['REQUEST_URI'];

// Handle POST request to create Checkout session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure we're sending JSON response
    header('Content-Type: application/json');

    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        // Debug log
        error_log('Received input: ' . print_r($input, true));

        // Validate input
        if (!$input || !isset($input['price_id'])) {
            throw new Exception('Invalid request: Missing price_id');
        }

        // Validate session
        if (!isset($_SESSION['company_id'])) {
            throw new Exception('No company ID found in session. Please log in.');
        }

        // Validate price ID format
        if (!preg_match('/^price_/', $input['price_id'])) {
            throw new Exception('Invalid price ID format');
        }

        // Validate price ID exists in our configuration
        $validPriceIds = array_values(STRIPE_PLAN_IDS);
        if (!in_array($input['price_id'], $validPriceIds)) {
            throw new Exception('Invalid price ID: Not found in configuration');
        }

        $successUrl = $protocol . $domain . $currentPath . '?success=1&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = $protocol . $domain . $currentPath . '?cancel=1';

        // Create checkout session
        $sessionId = $stripeManager->createCheckoutSession(
            $input['price_id'],
            $_SESSION['company_id'],
            $successUrl,
            $cancelUrl
        );

        if (!$sessionId) {
            throw new Exception('Failed to create checkout session: No session ID returned');
        }

        // Return success response
        echo json_encode([
            'success' => true,
            'sessionId' => $sessionId
        ]);
    } catch (Exception $e) {
        error_log('Stripe Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Subscription error',
            'message' => $e->getMessage(),
            'debug' => [
                'price_id' => $input['price_id'] ?? null,
                'company_id' => $_SESSION['company_id'] ?? null,
                'has_csrf' => isset($input['csrf_token'])
            ]
        ]);
    }
    exit;
}

// Set page title and body class for HTML output
$pageTitle = 'Subscribe to a Plan';
$bodyClass = 'theme-light';

// Cache configuration
$cacheFile = __DIR__ . '/cache/stripe_plans.cache';
$cacheTime = 30 * 60; // 30 minutes in seconds

// Include header (CSRF token meta tag is automatically added by header.php)
include_once __DIR__ . '/../includes/header.php';

// Function to get cached plans
function getCachedPlans($cacheFile, $cacheTime)
{
    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        if ($cacheData && time() - $cacheData['timestamp'] < $cacheTime) {
            return $cacheData['plans'];
        }
    }
    return null;
}

// Function to save plans to cache
function savePlansToCache($cacheFile, $plans)
{
    $cacheData['plans'] = $plans;
    $cacheData['timestamp'] = time();

    // Ensure cache directory exists
    if (!file_exists(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }
    file_put_contents($cacheFile, json_encode($cacheData));
}

// Try to get plans from cache first
$stripePlans = getCachedPlans($cacheFile, $cacheTime);

// If no valid cache exists, fetch from Stripe and cache the results
if ($stripePlans === null) {
    try {
        $stripePlans = $stripeManager->getPlans();
        savePlansToCache($cacheFile, $stripePlans);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Convert cached array back to objects for compatibility and create a lookup map
$stripePlanMap = [];
if ($stripePlans) {
    foreach ($stripePlans as $stripePlan) {
        $stripePlanMap[$stripePlan['id']] = [
            'unit_amount' => $stripePlan['unit_amount'],
            'interval' => $stripePlan['recurring']['interval']
        ];
    }
}

// Get displayable plans
$displayablePlans = getDisplayablePlans();

// Get current subscription
$stmt = $pdo->prepare("
    SELECT cs.plan_name, cs.status, cs.plan_id 
    FROM company_subscriptions cs 
    WHERE cs.company_id = ? 
    AND cs.status = 'active'
    ORDER BY cs.created_at DESC LIMIT 1
");
$stmt->execute([$_SESSION['company_id']]);
$currentSubscription = $stmt->fetch(PDO::FETCH_ASSOC);

// Map the database plan name to our defined constants
$currentPlanName = PLAN_FREE; // Default to free plan
if ($currentSubscription) {
    // Try to find a matching plan in our configuration
    foreach (SUBSCRIPTION_LIMITS as $planKey => $planDetails) {
        if (strtolower($currentSubscription['plan_name']) === strtolower($planKey)) {
            $currentPlanName = $planKey;
            break;
        }
    }
}

$hasPaidPlan = $currentSubscription && $currentPlanName !== PLAN_FREE;

// Handle subscription verification after successful payment
if (isset($_GET['success']) && isset($_GET['session_id'])) {
    try {
        $verifier = new SubscriptionVerifier($stripeManager, $pdo);

        // First check if this session has already been processed
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM company_subscriptions 
            WHERE session_id = ? AND status = 'active'
        ");
        $stmt->execute([$_GET['session_id']]);
        $existingSubscription = $stmt->fetchColumn();

        if ($existingSubscription > 0) {
            // This session has already been processed
            $successMessage = "<div class='alert alert-info'>
                <h4 class='alert-heading'>Subscription Already Processed</h4>
                <p>Your subscription has already been activated.</p>
                <p><a href='/dashboard.php' class='btn btn-primary mt-3'>Go to Dashboard</a></p>
            </div>";
        } else {
            $verificationResult = $verifier->verifySubscription($_GET['session_id'], $_SESSION['company_id']);

            if ($verificationResult) {
                try {
                    // Create success message
                    $successMessage = "<div class='alert alert-success'>
                        <h4 class='alert-heading mb-3'>Subscription Activated Successfully!</h4>
                        <p class='mb-3'>Your subscription has been activated. You can now access all features of your plan.</p>
                        <div class='mt-3'>
                            <a href='<?php echo BASE_URL ?>/dashboard.php' class='btn btn-primary'>Go to Dashboard</a>
                        </div>
                    </div>";

                    // Get subscription details
                    $subscriptionDetails = $stripeManager->getSubscriptionDetails($_GET['session_id']);
                } catch (Exception $e) {
                    error_log("Error processing subscription: " . $e->getMessage());
                    throw new Exception("Failed to process subscription: " . $e->getMessage());
                }
            } else {
                throw new Exception("Failed to verify subscription");
            }
        }
    } catch (Exception $e) {
        error_log("Subscription processing error: " . $e->getMessage());
        $errorMessage = "<div class='alert alert-danger'>
            <h4 class='alert-heading'>Error</h4>
            <p>An unexpected error occurred while processing your subscription.</p>
            <hr>
            <p class='mb-0'>Please contact support with reference: " . htmlspecialchars($_GET['session_id']) . "</p>
            <p class='text-muted small'>Error details: " . htmlspecialchars($e->getMessage()) . "</p>
        </div>";
    }
}

// Handle cancel redirect
if (isset($_GET['cancel'])) {
    $errorMessage = "<div class='alert alert-info'>
        <h4 class='alert-heading'>Subscription Cancelled</h4>
        <p>You have cancelled the subscription process.</p>
        <p>No charges have been made to your account.</p>
        <hr>
        <p class='mb-0'>You can try again whenever you're ready.</p>
    </div>";
}
?>

<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-credit-card me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z"></path>
                            <path d="M3 10l18 0"></path>
                            <path d="M7 15l.01 0"></path>
                            <path d="M11 15l2 0"></path>
                        </svg>
                        Subscription Plans
                    </h2>
                    <div class="text-muted mt-1">
                        <?php if ($hasPaidPlan): ?>
                            You are currently on the <strong><?php echo htmlspecialchars(SUBSCRIPTION_LIMITS[$currentPlanName]['name']); ?></strong> plan. Contact support if you need to change your subscription.
                        <?php else: ?>
                            Choose the plan that best fits your needs
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <!-- Display error if any -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php
            // Display success or error message
            if (isset($successMessage)) {
                echo $successMessage;
            } elseif (isset($errorMessage)) {
                echo $errorMessage;
            }
            ?>

            <!-- Display plans -->
            <div class="row row-cards">
                <?php
                foreach ($displayablePlans as $planName => $plan):
                    $stripePlanId = getStripePlanId($planName);
                    $stripePlanDetails = $stripePlanMap[$stripePlanId] ?? null;
                    $isCurrentPlan = ($planName === $currentPlanName);
                    $isPaidPlan = ($planName !== PLAN_FREE);

                    if (!$stripePlanDetails && $planName !== PLAN_FREE) {
                        continue; // Skip if no Stripe details found (except for free plan)
                    }
                ?>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-md <?php echo $plan['highlight'] ? 'border border-primary' : ''; ?>">
                            <?php if ($plan['highlight']): ?>
                                <div class="card-status-top bg-primary"></div>
                            <?php endif; ?>
                            <div class="card-body text-center">
                                <div class="text-uppercase text-muted font-weight-medium">
                                    <?php echo htmlspecialchars($plan['name']); ?>
                                    <?php if ($isCurrentPlan): ?>
                                        <span class="badge bg-success ms-2">Current Plan</span>
                                    <?php endif; ?>
                                </div>
                                <div class="display-5 fw-bold my-3">
                                    <?php if ($planName === PLAN_FREE): ?>
                                        Free
                                    <?php else: ?>
                                        $<?php echo number_format(($stripePlanDetails['unit_amount'] ?? 0) / 100, 2); ?>
                                        <span class="text-muted fs-3 fw-normal">/<?php echo htmlspecialchars($stripePlanDetails['interval'] ?? 'month'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted mb-3"><?php echo htmlspecialchars($plan['description']); ?></div>

                                <ul class="list-unstyled mt-4">
                                    <li class="mb-2">
                                        <span class="badge bg-primary-lt me-2">
                                            <?php echo formatLimitValue($plan['max_campaigns']); ?>
                                        </span>
                                        Campaigns
                                    </li>
                                    <li class="mb-2">
                                        <span class="badge bg-primary-lt me-2">
                                            <?php echo formatLimitValue($plan['max_backlinks_per_campaign']); ?>
                                        </span>
                                        Backlinks per Campaign
                                    </li>
                                    <li class="mb-2">
                                        <span class="badge bg-primary-lt me-2">
                                            <?php echo formatLimitValue($plan['max_total_backlinks']); ?>
                                        </span>
                                        Total Backlinks
                                    </li>

                                    <?php foreach ($plan['features'] as $feature => $details): ?>
                                        <li class="mb-2">
                                            <?php if ($details['available']): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check text-success me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M5 12l5 5l10 -10"></path>
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x text-danger me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M18 6l-12 12"></path>
                                                    <path d="M6 6l12 12"></path>
                                                </svg>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($details['description']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="text-center mt-4">
                                    <?php if ($isCurrentPlan): ?>
                                        <button class="btn btn-success w-100">Current Plan</button>
                                    <?php elseif ($hasPaidPlan): ?>
                                        <?php if ($planName === PLAN_FREE): ?>
                                            <button class="btn btn-outline-secondary w-100" disabled>Downgrade Not Allowed</button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary w-100" disabled>Contact Support to Change Plan</button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($planName === PLAN_FREE): ?>
                                            <button class="btn btn-primary w-100" disabled>Free Plan</button>
                                        <?php else: ?>
                                            <?php
                                            $stripePlanId = STRIPE_PLAN_IDS[$planName];
                                            if ($stripePlanId && $stripePlanId !== 'price_free'):
                                            ?>
                                                <button class="btn btn-primary w-100 subscribe-btn"
                                                    data-price-id="<?php echo htmlspecialchars($stripePlanId); ?>"
                                                    data-plan-name="<?php echo htmlspecialchars($plan['name']); ?>">
                                                    Subscribe
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-primary w-100" disabled>Not Available</button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
    // Initialize Stripe with publishable key
    const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');

    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Debug log for initialization
    console.log('Stripe initialized:', !!stripe);
    console.log('CSRF Token available:', !!csrfToken);

    // Handle subscription button click
    document.querySelectorAll('.subscribe-btn').forEach(button => {
        button.addEventListener('click', async () => {
            try {
                const priceId = button.getAttribute('data-price-id');
                const planName = button.getAttribute('data-plan-name');
                console.log('Attempting subscription:', {
                    planName: planName,
                    priceId: priceId
                });

                // Validate price ID
                if (!priceId || !priceId.startsWith('price_')) {
                    throw new Error(`Invalid price ID format for ${planName} plan`);
                }

                // Disable button and show loading state
                button.disabled = true;
                button.textContent = 'Processing...';

                // Fetch Checkout session ID
                const response = await fetch('subscribe.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        price_id: priceId,
                        csrf_token: csrfToken
                    })
                });

                let result;
                const responseText = await response.text();
                console.log('Raw server response:', responseText);

                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse server response:', e);
                    throw new Error('Server returned invalid JSON response');
                }

                console.log('Parsed server response:', result);

                if (!response.ok) {
                    throw new Error(result.message || `HTTP error! status: ${response.status}`);
                }

                if (!result.success || !result.sessionId) {
                    throw new Error(result.message || 'Failed to create checkout session');
                }

                console.log('Redirecting to Stripe checkout with session ID:', result.sessionId);

                // Redirect to Stripe Checkout
                const {
                    error
                } = await stripe.redirectToCheckout({
                    sessionId: result.sessionId
                });

                if (error) {
                    throw error;
                }
            } catch (error) {
                console.error('Subscription error:', error);
                alert('Error: ' + (error.message || 'An unexpected error occurred. Please try again.'));
            } finally {
                // Re-enable button and restore text
                button.disabled = false;
                button.textContent = 'Subscribe';
            }
        });
    });
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>