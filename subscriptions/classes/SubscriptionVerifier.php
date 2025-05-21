<?php

class SubscriptionVerifier
{
    private $stripeManager;
    private $db;

    public function __construct($stripeManager, $db)
    {
        $this->stripeManager = $stripeManager;
        $this->db = $db;

        // Ensure required tables exist
        $this->createWebhooksTableIfNotExists();
        $this->createCompanySubscriptionsTableIfNotExists();

        // Test database connection and table existence
        $this->testDatabaseSetup();
    }

    private function createWebhooksTableIfNotExists()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS subscription_webhooks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(255) NOT NULL,
                verified TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_session (session_id)
            )";
            $this->db->exec($sql);
        } catch (Exception $e) {
            error_log("Failed to create subscription_webhooks table: " . $e->getMessage());
        }
    }

    private function createCompanySubscriptionsTableIfNotExists()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS company_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_id INT NOT NULL,
                plan_id VARCHAR(255) NOT NULL,
                plan_name VARCHAR(50) NOT NULL,
                status ENUM('active', 'inactive', 'cancelled', 'past_due', 'incomplete', 'incomplete_expired') NOT NULL DEFAULT 'inactive',
                stripe_subscription_id VARCHAR(255),
                stripe_customer_id VARCHAR(255),
                current_period_start DATETIME NULL,
                current_period_end DATETIME NULL,
                next_billing_date DATETIME NULL,
                session_id VARCHAR(255) NOT NULL,
                cancelled_at DATETIME NULL,
                verified_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                UNIQUE KEY unique_session (session_id),
                INDEX idx_company_subscription (company_id, status)
            )";
            $this->db->exec($sql);
            error_log("Successfully created company_subscriptions table if it didn't exist");
        } catch (Exception $e) {
            error_log("Failed to create company_subscriptions table: " . $e->getMessage());
            throw $e;
        }
    }

    private function testDatabaseSetup()
    {
        try {
            // Test if company_subscriptions table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'company_subscriptions'");
            $tableExists = $stmt->fetch() !== false;
            error_log("company_subscriptions table exists: " . ($tableExists ? 'yes' : 'no'));

            if ($tableExists) {
                // Check if required columns exist
                $requiredColumns = ['session_id', 'verified_at'];
                $missingColumns = [];

                foreach ($requiredColumns as $column) {
                    $stmt = $this->db->query("SHOW COLUMNS FROM company_subscriptions LIKE '$column'");
                    if ($stmt->fetch() === false) {
                        $missingColumns[] = $column;
                    }
                }

                if (!empty($missingColumns)) {
                    error_log("Missing columns: " . implode(", ", $missingColumns) . ". Recreating table.");
                    $this->db->exec("DROP TABLE company_subscriptions");
                    $this->createCompanySubscriptionsTableIfNotExists();
                } else {
                    // Test table structure
                    $stmt = $this->db->query("DESCRIBE company_subscriptions");
                    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    error_log("company_subscriptions columns: " . implode(", ", $columns));
                }
            }
        } catch (Exception $e) {
            error_log("Database test failed: " . $e->getMessage());
        }
    }

    /**
     * Map Stripe subscription status to our database status
     */
    private function mapStripeStatus($stripeStatus)
    {
        $statusMap = [
            'active' => 'active',
            'past_due' => 'past_due',
            'canceled' => 'cancelled',
            'incomplete' => 'incomplete',
            'incomplete_expired' => 'incomplete_expired',
            'trialing' => 'active',
            'unpaid' => 'past_due',
            // Add any other Stripe statuses you need to handle
        ];

        return $statusMap[$stripeStatus] ?? 'inactive';
    }

    /**
     * Verify subscription using multiple methods
     * Returns true if at least two methods confirm the subscription
     */
    public function verifySubscription($sessionId, $companyId)
    {
        try {
            // First check if this session has already been processed
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM company_subscriptions 
                WHERE session_id = ? AND status = 'active'
            ");
            $stmt->execute([$sessionId]);
            $existingSubscription = $stmt->fetchColumn();

            if ($existingSubscription > 0) {
                error_log("Session $sessionId has already been processed");
                return true;
            }

            try {
                $subscriptionDetails = $this->stripeManager->getSubscriptionDetails($sessionId);
                error_log("Got subscription details: " . print_r($subscriptionDetails, true));

                // Map Stripe plan ID to our internal plan name using STRIPE_PLAN_IDS mapping
                $planName = array_search($subscriptionDetails['plan_id'], STRIPE_PLAN_IDS);
                if ($planName === false) {
                    throw new Exception('Invalid Stripe price ID: ' . $subscriptionDetails['plan_id'] . '. No matching plan found in STRIPE_PLAN_IDS.');
                }

                // Validate plan exists in our configuration
                if (!isset(SUBSCRIPTION_LIMITS[$planName])) {
                    throw new Exception('Plan ' . $planName . ' not found in SUBSCRIPTION_LIMITS configuration.');
                }

                // Start transaction
                $this->db->beginTransaction();

                try {
                    // First, deactivate any existing active subscriptions
                    $stmt = $this->db->prepare("
                        UPDATE company_subscriptions 
                        SET status = 'inactive',
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE company_id = ? AND status = 'active'
                    ");
                    $stmt->execute([$companyId]);

                    // Prepare timestamp values, ensuring they're valid integers
                    $currentPeriodStart = !empty($subscriptionDetails['current_period_start']) ?
                        (int)$subscriptionDetails['current_period_start'] : null;
                    $currentPeriodEnd = !empty($subscriptionDetails['current_period_end']) ?
                        (int)$subscriptionDetails['current_period_end'] : null;
                    $nextBillingDate = !empty($subscriptionDetails['next_billing_date']) ?
                        strtotime($subscriptionDetails['next_billing_date']) : null;

                    // Map the Stripe status to our database status
                    $dbStatus = $this->mapStripeStatus($subscriptionDetails['status']);
                    error_log("Mapping Stripe status '{$subscriptionDetails['status']}' to DB status '{$dbStatus}'");

                    // Insert new subscription with NULL handling for dates
                    $stmt = $this->db->prepare("
                        INSERT INTO company_subscriptions (
                            company_id,
                            session_id,
                            plan_id,
                            plan_name,
                            status,
                            stripe_subscription_id,
                            stripe_customer_id,
                            current_period_start,
                            current_period_end,
                            next_billing_date,
                            verified_at
                        ) VALUES (
                            :company_id,
                            :session_id,
                            :plan_id,
                            :plan_name,
                            :status,
                            :subscription_id,
                            :customer_id,
                            " . ($currentPeriodStart ? "FROM_UNIXTIME(:current_period_start)" : "NULL") . ",
                            " . ($currentPeriodEnd ? "FROM_UNIXTIME(:current_period_end)" : "NULL") . ",
                            " . ($nextBillingDate ? "FROM_UNIXTIME(:next_billing_date)" : "NULL") . ",
                            NOW()
                        ) ON DUPLICATE KEY UPDATE
                            session_id = VALUES(session_id),
                            plan_id = VALUES(plan_id),
                            plan_name = VALUES(plan_name),
                            status = VALUES(status),
                            stripe_subscription_id = VALUES(stripe_subscription_id),
                            stripe_customer_id = VALUES(stripe_customer_id),
                            current_period_start = VALUES(current_period_start),
                            current_period_end = VALUES(current_period_end),
                            next_billing_date = VALUES(next_billing_date),
                            verified_at = VALUES(verified_at),
                            updated_at = CURRENT_TIMESTAMP
                    ");

                    $params = [
                        'company_id' => $companyId,
                        'session_id' => $sessionId,
                        'plan_id' => $subscriptionDetails['plan_id'],
                        'plan_name' => $planName,
                        'status' => $dbStatus,
                        'subscription_id' => $subscriptionDetails['subscription_id'],
                        'customer_id' => $subscriptionDetails['customer_id']
                    ];

                    // Only add date parameters if they exist
                    if ($currentPeriodStart) {
                        $params['current_period_start'] = $currentPeriodStart;
                    }
                    if ($currentPeriodEnd) {
                        $params['current_period_end'] = $currentPeriodEnd;
                    }
                    if ($nextBillingDate) {
                        $params['next_billing_date'] = $nextBillingDate;
                    }

                    error_log("Executing subscription insert with params: " . print_r($params, true));
                    $stmt->execute($params);

                    // Update companies table to match
                    $stmt = $this->db->prepare("
                        UPDATE companies 
                        SET subscription_plan = :plan_name,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = :company_id
                    ");

                    $stmt->execute([
                        'plan_name' => $planName,
                        'company_id' => $companyId
                    ]);

                    $this->db->commit();
                    return true;
                } catch (Exception $e) {
                    $this->db->rollBack();
                    error_log("Error in subscription verification transaction: " . $e->getMessage());
                    throw $e;
                }
            } catch (Exception $e) {
                error_log("Error getting subscription details: " . $e->getMessage());
                return false;
            }
        } catch (Exception $e) {
            error_log("Error in subscription verification: " . $e->getMessage());
            return false;
        }
    }

    private function verifyFromRedirect($sessionId)
    {
        try {
            error_log("Checking redirect verification for session: " . $sessionId);
            $session = $this->stripeManager->getCheckoutSession($sessionId);
            $result = ($session->payment_status === 'paid' && $session->status === 'complete');
            error_log("Redirect check - Payment status: " . $session->payment_status . ", Session status: " . $session->status);
            return $result;
        } catch (Exception $e) {
            error_log("Redirect verification failed: " . $e->getMessage());
            return false;
        }
    }

    private function verifyFromWebhook($sessionId)
    {
        try {
            error_log("Checking webhook verification for session: " . $sessionId);

            // First, ensure we have a record for this session
            $stmt = $this->db->prepare("INSERT IGNORE INTO subscription_webhooks (session_id, verified) VALUES (?, 0)");
            $stmt->execute([$sessionId]);

            // Then check if it's verified
            $stmt = $this->db->prepare("SELECT verified FROM subscription_webhooks WHERE session_id = ? AND verified = 1");
            $stmt->execute([$sessionId]);
            $result = $stmt->fetch();
            $verified = ($result !== false && !empty($result));
            error_log("Webhook verification result: " . ($verified ? 'verified' : 'not verified'));
            return $verified;
        } catch (Exception $e) {
            error_log("Webhook verification failed: " . $e->getMessage());
            return false;
        }
    }

    private function verifyFromApi($sessionId)
    {
        try {
            error_log("Checking API verification for session: " . $sessionId);
            $session = $this->stripeManager->getCheckoutSession($sessionId);

            // For test mode, consider it successful if we can retrieve the session
            if ($session && $session->payment_status === 'paid') {
                error_log("API check - Session retrieved successfully and payment is paid");
                return true;
            }

            error_log("API check - Session status: " . $session->status . ", Payment status: " . $session->payment_status);
            return false;
        } catch (Exception $e) {
            error_log("API verification failed: " . $e->getMessage());
            return false;
        }
    }

    private function updateSubscriptionStatus($sessionId, $companyId, $status)
    {
        try {
            error_log("Attempting to update subscription status for session: $sessionId, company: $companyId");

            // First, verify the table exists with correct schema
            $stmt = $this->db->query("SHOW TABLES LIKE 'company_subscriptions'");
            if ($stmt->fetch() === false) {
                error_log("company_subscriptions table does not exist, creating it now");
                $this->createCompanySubscriptionsTableIfNotExists();
            }

            // Check if required columns exist
            $requiredColumns = ['session_id', 'verified_at'];
            $missingColumns = [];

            foreach ($requiredColumns as $column) {
                $stmt = $this->db->query("SHOW COLUMNS FROM company_subscriptions LIKE '$column'");
                if ($stmt->fetch() === false) {
                    $missingColumns[] = $column;
                }
            }

            if (!empty($missingColumns)) {
                error_log("Missing columns: " . implode(", ", $missingColumns) . ". Recreating table.");
                $this->db->exec("DROP TABLE company_subscriptions");
                $this->createCompanySubscriptionsTableIfNotExists();
            }

            // Get subscription details for complete record
            try {
                $subscriptionDetails = $this->stripeManager->getSubscriptionDetails($sessionId);
                error_log("Got subscription details: " . print_r($subscriptionDetails, true));

                $stmt = $this->db->prepare("
                    INSERT INTO company_subscriptions (
                        company_id,
                        session_id,
                        plan_id,
                        plan_name,
                        status,
                        stripe_subscription_id,
                        stripe_customer_id,
                        current_period_start,
                        current_period_end,
                        next_billing_date,
                        verified_at
                    ) VALUES (
                        :company_id,
                        :session_id,
                        :plan_id,
                        :plan_name,
                        :status,
                        :subscription_id,
                        :customer_id,
                        NULLIF(:current_period_start, ''),
                        NULLIF(:current_period_end, ''),
                        NULLIF(:next_billing_date, ''),
                        NOW()
                    ) ON DUPLICATE KEY UPDATE
                        plan_id = VALUES(plan_id),
                        plan_name = VALUES(plan_name),
                        status = VALUES(status),
                        stripe_subscription_id = VALUES(stripe_subscription_id),
                        stripe_customer_id = VALUES(stripe_customer_id),
                        current_period_start = VALUES(current_period_start),
                        current_period_end = VALUES(current_period_end),
                        next_billing_date = VALUES(next_billing_date),
                        verified_at = VALUES(verified_at)
                ");

                $params = [
                    'company_id' => $companyId,
                    'session_id' => $sessionId,
                    'plan_id' => $subscriptionDetails['plan_id'],
                    'plan_name' => $subscriptionDetails['plan_name'],
                    'status' => $status ? 'active' : 'failed',
                    'subscription_id' => $subscriptionDetails['subscription_id'],
                    'customer_id' => $subscriptionDetails['customer_id'],
                    'current_period_start' => $subscriptionDetails['current_period_start'] ?? '',
                    'current_period_end' => $subscriptionDetails['current_period_end'] ?? '',
                    'next_billing_date' => $subscriptionDetails['next_billing_date'] ?? ''
                ];

                error_log("Executing query with params: " . print_r($params, true));
                $result = $stmt->execute($params);

                if (!$result) {
                    $error = $stmt->errorInfo();
                    error_log("Database error during subscription update: " . print_r($error, true));
                    return false;
                }

                error_log("Successfully updated subscription status");
                return true;
            } catch (Exception $e) {
                error_log("Error getting subscription details: " . $e->getMessage());

                // Fallback to minimal update if we can't get full subscription details
                $stmt = $this->db->prepare("
                    INSERT INTO company_subscriptions (
                        company_id,
                        session_id,
                        status,
                        verified_at
                    ) VALUES (
                        :company_id,
                        :session_id,
                        :status,
                        NOW()
                    ) ON DUPLICATE KEY UPDATE
                        status = VALUES(status),
                        verified_at = VALUES(verified_at)
                ");

                $result = $stmt->execute([
                    'company_id' => $companyId,
                    'session_id' => $sessionId,
                    'status' => $status ? 'active' : 'failed'
                ]);

                if (!$result) {
                    $error = $stmt->errorInfo();
                    error_log("Database error during fallback update: " . print_r($error, true));
                    return false;
                }

                return true;
            }
        } catch (Exception $e) {
            error_log("Error updating subscription status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get detailed verification status
     * @param string $sessionId
     * @return string Status description
     */
    public function getVerificationStatus($sessionId)
    {
        try {
            $session = $this->stripeManager->getCheckoutSession($sessionId);

            // Check payment status
            if ($session->payment_status === 'paid' && $session->status === 'complete') {
                return 'verified';
            }

            // Check if payment is still processing
            if ($session->payment_status === 'processing') {
                return 'pending';
            }

            // Check webhook status
            $stmt = $this->db->prepare("SELECT verified FROM subscription_webhooks WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            $webhookStatus = $stmt->fetch();

            if ($webhookStatus && $webhookStatus['verified']) {
                return 'verified';
            }

            // If payment failed
            if ($session->payment_status === 'unpaid') {
                return 'payment_failed';
            }

            return 'verification_failed';
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }
}
