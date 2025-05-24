<?php

return [
    'use' => getenv('MAIL_DRIVER') ?: 'smtp', // Options: smtp, sendGrid, default

    'smtp' => [
        'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'port' => getenv('MAIL_PORT') ?: 587,
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from_email' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@your-domain.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Backlink Manager'
    ],

    'sendGrid' => [
        'host' => 'smtp.sendgrid.net',
        'username' => 'apikey',
        'password' => getenv('SENDGRID_API_KEY') ?: '',
        'port' => 587,
        'encryption' => 'tls',
        'from_email' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@your-domain.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Backlink Manager'
    ],

    'default' => [
        'from_email' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@your-domain.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Backlink Manager'
    ]
];
