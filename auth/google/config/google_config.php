<?php
// Google API configuration
define('GOOGLE_CLIENT_ID', '175923549427-vn2pg51d6ff3048j5rtr5v35f5d0cdrd.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URL', BASE_URL . 'auth/google/functions/google_callback.php');

// Required scopes for Google Sign-in
define('GOOGLE_SCOPES', [
    'email',
    'profile',
    'openid'
]);
