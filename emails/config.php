<?php

return [
    'use' => 'smtp', // Options: smtp, sendGrid, default

    'smtp' => [
        'host' => 'smtp.gmail.com', // Or your SMTP server
        'username' => 'your-email@gmail.com',
        'password' => 'your-app-specific-password',
        'port' => 587,
        'encryption' => 'tls',
        'from_email' => 'noreply@your-domain.com',
        'from_name' => 'Backlink Manager'
    ],

    'sendGrid' => [
        'host' => 'smtp.sendgrid.net',
        'username' => 'apikey',
        'password' => 'your_sendgrid_api_key',
        'port' => 587,
        'encryption' => 'tls',
        'from_email' => 'noreply@your-domain.com',
        'from_name' => 'Backlink Manager'
    ],

    'default' => [
        'from_email' => 'noreply@your-domain.com',
        'from_name' => 'Backlink Manager'
    ]
];
