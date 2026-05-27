<?php

return [
    'url' => env('SITE_URL', 'https://narepite.com'),

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

    'webapp' => [
        'name' => 'Создай уникальную песню для особенного момента',
        'description' => 'Создай уникальную песню с помощью ИИ за 2 минуты. Любой повод, любой стиль. Первый трек всего за 199₽. Подарок, признание, поздравление — сделай музыкой!',
        'application_category' => 'MusicApplication',
        'operating_system' => 'Web',
        'in_language' => 'ru',
        'url_path' => '/create-song',
        'feature_list' => [
            'Генерация текста ИИ',
            'Выбор жанра',
            'Выбор вокала',
            'Поддержка 6 языков',
            'Мгновенная генерация',
            'Редактирование текста',
            'Перевод песни',
        ],
        'offer' => [
            'price' => 199,
            'price_currency' => 'RUB',
            'price_valid_until' => '2027-12-31',
            'availability' => 'https://schema.org/InStock',
            'url_path' => '/tarify',
        ],
    ],

    'best_songs' => [
        'image_width' => 200,
        'image_height' => 200,
        'mirror_domains' => [
            'narepite.site' => 'narepite.com',
        ],
    ],

    'tariff' => [
        'page_slug' => 'tarify',
        'url_path' => '/tarify',
        'name' => 'Тарифы на генерацию песен',
        'service_type' => 'Генерация персональной песни',
        'description' => 'Пакеты на создание песен через ИИ.',
        'area_served' => 'RU',
        'offer_catalog_name' => 'Пакеты песен',
        'price_currency' => 'RUB',
        'unit_text' => 'songs',
    ],

    'help' => [
        'page_slug' => 'help',
        'url_path' => '/help',
        'contact_type' => 'customer support',
        'available_language' => ['Russian'],
    ],
];
