<?php

return [
    'shop_id' => env('YOOKASSA_SHOP_ID'),
    'secret_key' => env('YOOKASSA_SECRET_KEY'),
    'return_url' => env('YOOKASSA_RETURN_URL', 'https://narepite.site/payment/success'),
    
    // Пакеты песен
    'packages' => [
        2 => ['price' => 499, 'name' => '2 песни'],
        7 => ['price' => 999, 'name' => '7 песен'],
        30 => ['price' => 2999, 'name' => '30 песен'],
    ],
];