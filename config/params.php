<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',

    'currency' => [
        'url' => 'https://www.cbr-xml-daily.ru/daily_json.js',
        'currency' => ['USD', 'EUR', 'CNY', 'JPY'],
        'currencyChar' => [
            'USD' => 'Доллар США',
            'EUR' => 'Евро',
            'CNY' => 'Китайский юань',
            'JPY' => 'Японских иен',
            'RUB' => 'Российский рубль'
        ],
        'currencyColor' => [
            'USD' => 'Red',
            'EUR' => 'Yellow',
            'CNY' => 'Blue',
            'JPY' => 'Fuchsia',
            'RUB' => 'Black'
        ],
        'rub' => 'RUB'
    ]
];
