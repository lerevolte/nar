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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],

    'suno' => [
        'api_key' => env('SUNO_API_KEY'),
        'api_url' => env('SUNO_API_URL', 'https://api.sunoapi.org/api/v1'),
    ],

    'ai_provider' => env('AI_PROVIDER', 'openai'),
    'yandex_metrica' => [
        'oauth_token' => env('YANDEX_METRICA_OAUTH_TOKEN'),
    ],
    'kie' => [
        'api_key' => env('KIE_API'),
        'api_url' => env('KIE_API_URL', 'https://api.kie.ai/api/v1'),
    ],

    // Хранилище пользовательских медиа (музыка, обложки, загрузки).
    // driver=local — пишем в public_path как раньше; driver=s3 — в бакет S3.
    'media' => [
        'driver' => env('MEDIA_DISK', 'local'),
        // Базовый URL при driver=local (отдаётся Apache из public/).
        'local_url' => env('MEDIA_LOCAL_URL', 'https://narepite.site'),
    ],

    // Операции над треками (extend / cover / add-instrumental и т.д.)
    'track_ops' => [
        // На старте функции включены только для этих user_id (обкатка на админе).
        // Пусто/звёздочка снимет ограничение (доступно всем).
        'allowed_user_ids' => array_filter(array_map(
            'trim',
            explode(',', (string) env('TRACK_OPS_USER_IDS', '288559694'))
        )),
        // Дефолтная модель генерации для новых операций.
        'model' => env('TRACK_OPS_MODEL', 'V5_5'),
        // Лимиты на загружаемые пользователем аудиофайлы.
        'upload_max_mb' => (int) env('TRACK_OPS_UPLOAD_MAX_MB', 20),
        'upload_max_seconds' => (int) env('TRACK_OPS_UPLOAD_MAX_SECONDS', 480),
    ],
];
