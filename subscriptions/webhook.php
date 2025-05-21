<?php
// webhook.php

// Include configuration and database
require_once __DIR__ . '/../config/db.php'; // Database connection
require_once __DIR__ . '/config/stripe.php'; // Stripe configuration
require_once __DIR__ . '/classes/StripeManager.php';
require_once __DIR__ . '/classes/SubscriptionVerifier.php';

// Stripe webhook secret (get this from Stripe Dashboard)
$webhookSecret = 'whsec_...'; // Replace with your webhook secret

$stripeManager = new StripeManager();
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        STRIPE_WEBHOOK_SECRET
    );
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit();
}

// Handle the event
switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;

        // Store webhook verification
        $stmt = $pdo->prepare("
            INSERT INTO subscription_webhooks 
            (session_id, event_type, verified, created_at) 
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE 
            verified = 1, updated_at = NOW()
        ");

        $stmt->execute([
            $session->id,
            $event->type
        ]);

        // Trigger subscription verification
        if (isset($session->metadata->company_id)) {
            $verifier = new SubscriptionVerifier($stripeManager, $pdo);
            $verifier->verifySubscription($session->id, $session->metadata->company_id);
        }
        break;

        // Add other event types as needed
}

http_response_code(200);
