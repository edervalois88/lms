<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_NOTIFICATIONS_CHANNEL'),
        ],
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
        'fallback_model' => env('GROQ_FALLBACK_MODEL', 'llama-3.3-70b-versatile'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'timeout_seconds' => (int) env('GROQ_TIMEOUT_SECONDS', 12),
        'retry_times' => (int) env('GROQ_RETRY_TIMES', 1),
        'retry_sleep_ms' => (int) env('GROQ_RETRY_SLEEP_MS', 250),
        'max_tokens_small' => (int) env('GROQ_MAX_TOKENS_SMALL', 140),
        'max_tokens_medium' => (int) env('GROQ_MAX_TOKENS_MEDIUM', 220),
        'max_tokens_large' => (int) env('GROQ_MAX_TOKENS_LARGE', 320),
        'cache_ttl_seconds' => (int) env('GROQ_CACHE_TTL_SECONDS', 900),
        'tutor_rate_limit_per_minute' => (int) env('GROQ_TUTOR_RATE_LIMIT_PER_MINUTE', 8),
    ],

    'vector' => [
        'provider' => env('VECTOR_PROVIDER', 'qdrant'),
        'url' => env('VECTOR_URL'),
        'api_key' => env('VECTOR_API_KEY'),
        'collection' => env('VECTOR_COLLECTION', 'exam_context'),
    ],

];
