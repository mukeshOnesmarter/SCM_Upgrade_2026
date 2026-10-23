<?php

return [

    'symbols' => [
        'USD' => '$',
        'INR' => '₹',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
    ],

    // Fetch the currency symbol dynamically based on the `.env` setting
    'default' => env('APP_CURRENCY', 'USD'),

    'symbol' => env('APP_CURRENCY') ? ([
        'USD' => '$',
        'INR' => '₹',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
    ][env('APP_CURRENCY')] ?? '$') : '$',
];
