<?php

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'bot_username' => env('TELEGRAM_BOT_USERNAME'),

    // Для Mini App
    'webapp_url' => env('APP_URL'),

    // Время жизни сессии (24 часа)
    'session_lifetime' => 60 * 24,

    // ID администраторов для служебных уведомлений (оплаты и т.п.)
    'admin_ids' => [288559694, 154483653],
];
