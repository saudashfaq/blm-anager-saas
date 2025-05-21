<?php
// config/stripe.php

// Stripe API keys (replace with your actual keys)

// Include the Stripe PHP SDK
require_once __DIR__ . '/../stripe-php/init.php';

// Set the Stripe API key
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
