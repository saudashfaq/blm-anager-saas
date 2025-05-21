<?php

require_once __DIR__ . '/../../config/subscription_plans.php';

class SubscriptionSessionManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Load subscription data into session for a company
     * @param int $companyId
     * @param bool $isSuperAdmin
     * @return array|null Subscription data if found
     */
    public function loadSubscriptionData($companyId, $isSuperAdmin = false): ?array
    {
        try {
            // If superadmin, return superadmin plan from config
            if ($isSuperAdmin) {
                $superAdminPlan = [
                    'plan_name' => PLAN_SUPERADMIN,
                    'status' => 'active',
                    'limits' => SUBSCRIPTION_LIMITS[PLAN_SUPERADMIN]
                ];
                $_SESSION['subscription'] = $superAdminPlan;
                return $superAdminPlan;
            }

            // Check if any subscription exists for the company (regardless of status)
            $stmt = $this->db->prepare("
                SELECT 
                    cs.plan_name,
                    cs.plan_id,
                    cs.status,
                    cs.stripe_subscription_id,
                    cs.stripe_customer_id,
                    cs.current_period_start,
                    cs.current_period_end,
                    cs.next_billing_date
                FROM company_subscriptions cs
                WHERE cs.company_id = ?
                ORDER BY cs.created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$companyId]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($subscription) {
                // Get plan limits from subscription_plans.php
                $planLimits = SUBSCRIPTION_LIMITS[$subscription['plan_name']] ?? null;

                if ($planLimits) {
                    $subscriptionData = [
                        'plan_name' => $subscription['plan_name'],
                        'plan_id' => $subscription['plan_id'],
                        'status' => $subscription['status'],
                        'stripe_subscription_id' => $subscription['stripe_subscription_id'],
                        'stripe_customer_id' => $subscription['stripe_customer_id'],
                        'current_period_start' => $subscription['current_period_start'],
                        'current_period_end' => $subscription['current_period_end'],
                        'next_billing_date' => $subscription['next_billing_date'],
                        'limits' => $planLimits
                    ];

                    $_SESSION['subscription'] = $subscriptionData;
                    return $subscriptionData;
                }
            }

            // Only assign free plan if there's no subscription record at all
            return $this->assignFreePlan($companyId);
        } catch (Exception $e) {
            error_log("Error loading subscription data: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Store subscription data in session after successful subscription
     * @param array $subscriptionDetails Details from Stripe
     * @return bool Success status
     */
    public function storeNewSubscription(array $subscriptionDetails): bool
    {
        try {
            // Get plan name from Stripe plan ID
            $planName = getPlanNameFromStripeId($subscriptionDetails['plan_id']);
            $planLimits = SUBSCRIPTION_LIMITS[$planName] ?? null;

            if (!$planLimits) {
                throw new Exception("Invalid plan name: {$planName}");
            }

            $subscriptionData = [
                'plan_name' => $planName,
                'plan_id' => $subscriptionDetails['plan_id'],
                'status' => $subscriptionDetails['status'],
                'stripe_subscription_id' => $subscriptionDetails['subscription_id'],
                'stripe_customer_id' => $subscriptionDetails['customer_id'],
                'current_period_start' => date('Y-m-d H:i:s', $subscriptionDetails['current_period_start']),
                'current_period_end' => date('Y-m-d H:i:s', $subscriptionDetails['current_period_end']),
                'next_billing_date' => $subscriptionDetails['next_billing_date'],
                'limits' => $planLimits
            ];

            $_SESSION['subscription'] = $subscriptionData;
            return true;
        } catch (Exception $e) {
            error_log("Error storing subscription data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign free plan to a company - only if no subscription exists
     * @param int $companyId
     * @return array Free plan subscription data
     */
    public function assignFreePlan($companyId): array
    {
        try {
            // Double check no subscription exists to prevent duplicates
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM company_subscriptions 
                WHERE company_id = ?
            ");
            $stmt->execute([$companyId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                throw new Exception("Company already has a subscription record");
            }

            // Insert free plan subscription record
            $stmt = $this->db->prepare(
                "INSERT INTO company_subscriptions (
                    company_id,
                    plan_name,
                    plan_id,
                    status,
                    current_period_start,
                    current_period_end,
                    session_id
                ) VALUES (
                    :company_id,
                    :plan_name,
                    :plan_id,
                    'active',
                    CURRENT_TIMESTAMP,
                    DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 YEAR),
                    'free_plan'
                )"
            );
            $stmt->execute([
                'company_id' => $companyId,
                'plan_name' => PLAN_FREE,
                'plan_id' => getStripePlanId(PLAN_FREE)
            ]);

            // Update company record
            $stmt = $this->db->prepare(
                "UPDATE companies SET 
                subscription_plan = :plan_name,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :company_id"
            );
            $stmt->execute([
                'plan_name' => PLAN_FREE,
                'company_id' => $companyId
            ]);

            // Create free plan session data
            $freePlan = [
                'plan_name' => PLAN_FREE,
                'plan_id' => getStripePlanId(PLAN_FREE),
                'status' => 'active',
                'current_period_start' => date('Y-m-d H:i:s'),
                'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 year')),
                'limits' => SUBSCRIPTION_LIMITS[PLAN_FREE]
            ];

            $_SESSION['subscription'] = $freePlan;
            return $freePlan;
        } catch (Exception $e) {
            error_log("Error assigning free plan: " . $e->getMessage());
            // Return basic free plan without database record in case of error
            $basicFreePlan = [
                'plan_name' => PLAN_FREE,
                'plan_id' => getStripePlanId(PLAN_FREE),
                'status' => 'active',
                'limits' => SUBSCRIPTION_LIMITS[PLAN_FREE]
            ];
            $_SESSION['subscription'] = $basicFreePlan;
            return $basicFreePlan;
        }
    }

    /**
     * Clear subscription data from session
     */
    public function clearSubscriptionData(): void
    {
        unset($_SESSION['subscription']);
    }

    /**
     * Check if user has an active subscription
     * @return bool
     */
    public function hasActiveSubscription(): bool
    {
        return isset($_SESSION['subscription']) &&
            $_SESSION['subscription']['status'] === 'active';
    }

    /**
     * Get current subscription data
     * @return array|null
     */
    public function getCurrentSubscription(): ?array
    {
        return $_SESSION['subscription'] ?? null;
    }

    /**
     * Handle subscription assignment during registration
     * @param int $companyId
     * @param bool $isSuperAdmin
     * @return array|null
     */
    public function handleNewRegistration($companyId, $isSuperAdmin = false): ?array
    {
        try {
            if ($isSuperAdmin) {
                // Superadmins don't need a subscription record
                return $this->loadSubscriptionData($companyId, true);
            }

            // For regular users, assign the free plan
            return $this->assignFreePlan($companyId);
        } catch (Exception $e) {
            error_log("Error handling new registration subscription: " . $e->getMessage());
            return null;
        }
    }
}
