<?php
// config/stripe.php

// Stripe API keys (replace with your actual keys)
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: ''); // Your Stripe secret key
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: ''); // Your Stripe publishable key

// Include the Stripe PHP SDK
require_once __DIR__ . '/../stripe-php/init.php';

// Set the Stripe API key
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
