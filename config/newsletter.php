<?php

return [
    'double_opt_in_email' => env('NEWSLETTER_DOUBLE_OPT_IN_EMAIL', true),

    'confirm_token_ttl_hours' => (int) env('NEWSLETTER_CONFIRM_TOKEN_TTL_HOURS', 48),

    'max_send_attempts' => (int) env('NEWSLETTER_MAX_SEND_ATTEMPTS', 3),

    'rate_limit_per_minute' => (int) env('NEWSLETTER_RATE_LIMIT_PER_MINUTE', 60),

    'email' => [
        'provider' => env('EMAIL_PROVIDER', env('MAIL_MAILER', 'log')),
        'from_address' => env('EMAIL_FROM', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
        'from_name' => env('EMAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Protein Point')),
        'api_key' => env('EMAIL_API_KEY'),
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'meta'),
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v21.0'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
    ],
];
