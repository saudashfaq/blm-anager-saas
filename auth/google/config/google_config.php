<?php
// Google API configuration
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');

// Construct the redirect URL if not provided in environment
$redirectUrl = getenv('GOOGLE_REDIRECT_URL');
if (empty($redirectUrl)) {
    $redirectUrl = rtrim(SITE_URL, '/') . '/auth/google/functions/google_callback.php';
}
define('GOOGLE_REDIRECT_URL', $redirectUrl);

// Required scopes for Google Sign-in
define('GOOGLE_SCOPES', [
    'email',
    'profile',
    'openid'
]);
