<?php
// Subscription plan constants and limitations

// Plan Names/IDs - these should match with Stripe plan metadata names
const PLAN_FREE = 'free';
const PLAN_BASIC = 'basic';
const PLAN_PREMIUM = 'premium';
const PLAN_ENTERPRISE = 'enterprise';
const PLAN_SUPERADMIN = 'superadmin';

// Stripe Plan IDs - these should match with your Stripe dashboard
const STRIPE_PLAN_IDS = [
    PLAN_FREE => 'price_free', // This is a placeholder, free plan doesn't need a Stripe ID
    PLAN_BASIC => 'price_1ROH1W2MOP7tABA36NXo1bpS',
    PLAN_PREMIUM => 'price_1ROH462MOP7tABA3dBbLpKHp',
    PLAN_ENTERPRISE => 'price_1ROH8I2MOP7tABA3yrZiB7Il'
];

// Define plan limitations and display settings
const SUBSCRIPTION_LIMITS = [
    PLAN_FREE => [
        'name' => 'Free',
        'description' => 'Perfect for trying out our service',
        'display' => true, // Whether to show this plan on the frontend
        'highlight' => false, // Whether to highlight this plan
        'max_campaigns' => 1,
        'max_total_backlinks' => 10,
        'max_backlinks_per_campaign' => 10,
        'verification_frequency' => 'weekly',
        'features' => [
            'export_reports' => [
                'available' => false,
                'description' => 'Export campaign reports'
            ],
            'api_access' => [
                'available' => false,
                'description' => 'API access'
            ],
            'email_notifications' => [
                'available' => false,
                'description' => 'Email notifications'
            ],
            'bulk_upload' => [
                'available' => false,
                'description' => 'Bulk backlink upload'
            ]
        ]
    ],
    PLAN_BASIC => [
        'name' => 'Basic',
        'description' => 'Great for small businesses',
        'display' => true,
        'highlight' => false,
        'max_campaigns' => 5,
        'max_total_backlinks' => 500,
        'max_backlinks_per_campaign' => 100,
        'verification_frequency' => 'weekly',
        'features' => [
            'export_reports' => [
                'available' => true,
                'description' => 'Export campaign reports'
            ],
            'api_access' => [
                'available' => false,
                'description' => 'API access'
            ],
            'email_notifications' => [
                'available' => true,
                'description' => 'Email notifications'
            ],
            'bulk_upload' => [
                'available' => true,
                'description' => 'Bulk backlink upload'
            ]
        ]
    ],
    PLAN_PREMIUM => [
        'name' => 'Premium',
        'description' => 'Perfect for growing businesses',
        'display' => true,
        'highlight' => true,
        'max_campaigns' => 10,
        'max_total_backlinks' => 1100,
        'max_backlinks_per_campaign' => 150,
        'verification_frequency' => 'every_two_weeks',
        'features' => [
            'export_reports' => [
                'available' => true,
                'description' => 'Export campaign reports'
            ],
            'api_access' => [
                'available' => true,
                'description' => 'API access'
            ],
            'email_notifications' => [
                'available' => true,
                'description' => 'Email notifications'
            ],
            'bulk_upload' => [
                'available' => true,
                'description' => 'Bulk backlink upload'
            ]
        ]
    ],
    PLAN_ENTERPRISE => [
        'name' => 'Enterprise',
        'description' => 'For large organizations',
        'display' => true,
        'highlight' => false,
        'max_campaigns' => 25,
        'max_total_backlinks' => 2800,
        'max_backlinks_per_campaign' => 200,
        'verification_frequency' => 'monthly',
        'features' => [
            'export_reports' => [
                'available' => true,
                'description' => 'Export campaign reports'
            ],
            'api_access' => [
                'available' => true,
                'description' => 'API access'
            ],
            'email_notifications' => [
                'available' => true,
                'description' => 'Email notifications'
            ],
            'bulk_upload' => [
                'available' => true,
                'description' => 'Bulk backlink upload'
            ],
            'priority_support' => [
                'available' => true,
                'description' => 'Priority support'
            ]
        ]
    ],
    PLAN_SUPERADMIN => [
        'name' => 'Superadmin',
        'description' => 'Administrative access',
        'display' => false, // Hide from frontend
        'highlight' => false,
        'max_campaigns' => -1,
        'max_total_backlinks' => -1,
        'max_backlinks_per_campaign' => -1,
        'verification_frequency' => 'realtime',
        'features' => [
            'export_reports' => [
                'available' => true,
                'description' => 'Export campaign reports'
            ],
            'api_access' => [
                'available' => true,
                'description' => 'API access'
            ],
            'email_notifications' => [
                'available' => true,
                'description' => 'Email notifications'
            ],
            'bulk_upload' => [
                'available' => true,
                'description' => 'Bulk backlink upload'
            ],
            'admin_access' => [
                'available' => true,
                'description' => 'Full administrative access'
            ]
        ]
    ]
];

// Helper function to get plan limits
function getPlanLimits($planName)
{
    return SUBSCRIPTION_LIMITS[$planName] ?? SUBSCRIPTION_LIMITS[PLAN_FREE];
}

// Helper function to check if user has reached their limit
function hasReachedLimit($planName, $limitType, $currentCount)
{
    $limits = getPlanLimits($planName);
    $maxLimit = $limits[$limitType] ?? 0;

    // -1 indicates unlimited
    if ($maxLimit === -1) {
        return false;
    }

    return $currentCount >= $maxLimit;
}

// Helper function to get Stripe plan ID
function getStripePlanId($planName)
{
    return STRIPE_PLAN_IDS[$planName] ?? null;
}

// Helper function to get plan name from Stripe plan ID
function getPlanNameFromStripeId($stripePlanId)
{
    return array_search($stripePlanId, STRIPE_PLAN_IDS) ?? PLAN_FREE;
}

// Helper function to get displayable plans
function getDisplayablePlans()
{
    return array_filter(SUBSCRIPTION_LIMITS, function ($plan) {
        return $plan['display'] === true;
    });
}

// Helper function to format limit value for display
function formatLimitValue($value)
{
    return $value === -1 ? 'Unlimited' : number_format($value);
}
