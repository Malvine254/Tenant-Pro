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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mpesa' => [
        'environment' => env('MPESA_ENV', 'sandbox'),
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'shortcode' => env('MPESA_SHORTCODE', '174379'),
        'passkey' => env('MPESA_PASSKEY'),
        'callback_url' => env('MPESA_CALLBACK_URL'),
        // Sandbox defaults to local completion so development does not depend
        // on Daraja availability. Production can never enter the simulation
        // branch because PaymentController also requires environment=sandbox.
        'simulate' => env('MPESA_SIMULATE', true),
    ],

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'credentials_json' => env('FIREBASE_CREDENTIALS_JSON'),
        'credentials_base64' => env('FIREBASE_CREDENTIALS_BASE64'),
    ],

    'azure_openai' => [
        'enabled' => env('AZURE_OPENAI_ENABLED', false),
        'endpoint' => env('AZURE_OPENAI_ENDPOINT'),
        'api_key' => env('AZURE_OPENAI_API_KEY'),
        'deployment' => env('AZURE_OPENAI_DEPLOYMENT'),
        'api_version' => env('AZURE_OPENAI_API_VERSION', '2024-10-21'),
        'temperature' => (float) env('AZURE_OPENAI_TEMPERATURE', 0.3),
        'max_output_tokens' => (int) env('AZURE_OPENAI_MAX_OUTPUT_TOKENS', 800),
        'request_timeout' => (int) env('AZURE_OPENAI_TIMEOUT', 30),
        'max_tool_iterations' => (int) env('AZURE_OPENAI_MAX_TOOL_ITERATIONS', 5),
        // Number of most recent conversation messages sent as context to the model.
        'context_message_limit' => (int) env('AZURE_OPENAI_CONTEXT_MESSAGE_LIMIT', 20),
    ],

];
