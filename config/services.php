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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],
    'qontak' => [
        'api_url' => env('QONTAK_API_URL', 'https://api.mekari.com/qontak/chat/v1/broadcasts/whatsapp/direct'),
        'client_id' => env('QONTAK_CLIENT_ID'),
        'client_secret' => env('QONTAK_CLIENT_SECRET'),
        'integration_id' => env('QONTAK_CHANNEL_INTEGRATION_ID'),
        'template_id' => env('QONTAK_TEMPLATE_ID'),
        'sellphone_template_id' => env('QONTAK_SELLPHONE_TEMPLATE_ID', env('QONTAK_TEMPLATE_ID')),
    ],

    'ninerouter' => [
        'base_url' => env('NINEROUTER_API_BASE', env('OPENAI_URL', 'https://api.9router.com/v1')),
        'api_key' => env('NINEROUTER_API_KEY', env('OPENAI_API_KEY', '')),
        'model' => env('NINEROUTER_MODEL', 'groq/openai/gpt-oss-120b'),
        'timeout' => (int) env('NINEROUTER_TIMEOUT', 90),
        'temperature' => (float) env('NINEROUTER_TEMPERATURE', 0.4),
        'max_tokens' => (int) env('NINEROUTER_MAX_TOKENS', 2000),
    ],

    'n8n' => [
        'agent_token' => env('N8N_AGENT_TOKEN', 'zedpos-2026-banjarbaru'),
    ],

    'crm_wa' => [
        'enabled' => env('CRM_WA_ENABLED', true),
        'api_url' => env('CRM_WA_API_URL', 'https://arbitrate-prelaw-poplar.ngrok-free.dev/api/zedpos/nota'),
        'token' => env('CRM_WA_TOKEN', 'B-M515tMiSLtAK3f5GL_8au7bY4eaOsaBuhs0W6kPqA'),
        'channel_integration_id' => env('CRM_WA_CHANNEL_INTEGRATION_ID', '56b60c3c-0123-46af-958b-32f3ad12ee37'),
        'template_id' => env('CRM_WA_TEMPLATE_ID', '380d1355-0a65-4dc5-be82-308ee7619910'),
        'sellphone_template_id' => env('CRM_WA_SELLPHONE_TEMPLATE_ID', '380d1355-0a65-4dc5-be82-308ee7619910'),
        'auto_send_order' => env('CRM_WA_AUTO_SEND_ORDER', true),
        'auto_send_sellphone' => env('CRM_WA_AUTO_SEND_SELLPHONE', true),
    ],

];
