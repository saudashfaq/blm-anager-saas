<?php
// config/stripe.php

// Stripe API keys (replace with your actual keys)
//define('STRIPE_SECRET_KEY', 'sk_test_51RNssO2MOP7tABA3D0DJe2aPFz0KldaqogwMNLipjLf3yA1Om3pPLKMvdenMi5UfHP5c82WKkFQrr7huVIOdKk2j00mcK5JJY7'); // Your Stripe secret key
//define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51RNssO2MOP7tABA33LKW03rHhhpKin9dp0N2zvz4DnKU9bFa8njcvvunqVbnBl9pnSvbg4VRtG3l5OXmSV0eNBhI00KdTy3jpZ'); // Your Stripe publishable key

// Include the Stripe PHP SDK
require_once __DIR__ . '/../stripe-php/init.php';

// Set the Stripe API key
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
