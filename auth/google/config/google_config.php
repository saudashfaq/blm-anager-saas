<?php
// Google API configuration
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URL', BASE_URL . 'auth/google/functions/google_callback.php');

// Required scopes for Google Sign-in
define('GOOGLE_SCOPES', [
    'email',
    'profile',
    'openid'
]);
