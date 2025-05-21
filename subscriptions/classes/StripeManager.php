<?php
// classes/StripeManager.php

class StripeManager
{
    /**
     * Fetch all active prices (plans) from Stripe.
     * @return array List of active prices with their associated products
     */
    public function getPlans()
    {
        try {
            // Fetch all active prices
            $prices = \Stripe\Price::all([
                'active' => true,
                'expand' => ['data.product'] // Include product details
            ]);
            /*
            echo '<pre>';
            print_r($prices->data);
            echo '</pre>';
            */
            return $prices->data;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception("Failed to fetch Stripe plans: " . $e->getMessage());
        }
    }

    /**
     * Create a Stripe Checkout session for a subscription.
     * @param string $priceId Stripe Price ID
     * @param string $companyId Company ID for metadata
     * @param string $successUrl URL to redirect after success
     * @param string $cancelUrl URL to redirect after cancellation
     * @return string Checkout session ID
     */
    public function createCheckoutSession($priceId, $companyId, $successUrl, $cancelUrl)
    {
        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'company_id' => $companyId // Store company ID for webhook
                ]
            ]);
            return $session->id;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception("Failed to create Stripe Checkout session: " . $e->getMessage());
        }
    }

    /**
     * Get Checkout Session details
     * @param string $sessionId
     * @return \Stripe\Checkout\Session
     */
    public function getCheckoutSession($sessionId)
    {
        try {
            return \Stripe\Checkout\Session::retrieve($sessionId);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception("Failed to retrieve checkout session: " . $e->getMessage());
        }
    }

    /**
     * Get Subscription details by session ID
     * @param string $sessionId
     * @return \Stripe\Subscription
     */
    public function getSubscriptionBySession($sessionId)
    {
        try {
            $session = $this->getCheckoutSession($sessionId);
            if (!$session->subscription) {
                throw new Exception("No subscription found for session");
            }
            return \Stripe\Subscription::retrieve($session->subscription);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception("Failed to retrieve subscription: " . $e->getMessage());
        }
    }

    /**
     * Get subscription details for display
     * @param string $sessionId
     * @return array Subscription details
     */
    public function getSubscriptionDetails($sessionId)
    {
        try {
            $session = $this->getCheckoutSession($sessionId);
            if (!$session->subscription) {
                throw new Exception("No subscription found for session");
            }

            $subscription = \Stripe\Subscription::retrieve($session->subscription);
            $product = \Stripe\Product::retrieve($subscription->plan->product);

            return [
                'plan_name' => $product->name,
                'plan_id' => $subscription->plan->id,
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer,
                'interval' => $subscription->plan->interval,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'next_billing_date' => date('Y-m-d', $subscription->current_period_end),
                'status' => $subscription->status
            ];
        } catch (\Exception $e) {
            throw new Exception("Failed to retrieve subscription details: " . $e->getMessage());
        }
    }
}
