<?php

return [
    'url' => env('APP_URL', 'https://narepite.com'),

    'organization' => [
        'name' => 'На Репите',
        'email' => env('SITE_EMAIL', 'support@narepite.com'),
        'logo' => [
            'path' => '/img/logo1.svg',
            'width' => 176,
            'height' => 38,
        ],
        'same_as' => [
            'https://t.me/na_repitebot',
            'https://max.ru/id501216944367_bot',
        ],
        'contact_point' => [
            'contact_type' => 'customer support',
            'available_language' => ['Russian'],
        ],
    ],

    'website' => [
        'name' => 'На Репите',
        'description' => 'Сайт для создания песен: нейросеть запишет музыку высокого качества, ИИ создаст текст и вокал по вашим словам. Полностью на русском языке. Создайте свой трек всего за 3 шага!',
    ],
];
