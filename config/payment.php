<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment driver that will be used
    | by the framework. You may set this to any of the drivers defined
    | in the "drivers" configuration array.
    |
    */

    'default' => env('PAYMENT_DRIVER', 'zarinpal-sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Payment Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the payment drivers for your application.
    | You may configure multiple drivers and switch between them as needed.
    |
    */

    'drivers' => [
        'zarinpal' => [
            'apiPurchaseUrl' => 'https://www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json',
            'apiPaymentUrl' => 'https://www.zarinpal.com/pg/StartPay/',
            'apiVerificationUrl' => 'https://www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json',
            'merchantId' => env('ZARINPAL_MERCHANT_ID'),
            'callbackUrl' => env('ZARINPAL_CALLBACK_URL', env('APP_URL') . '/payment/callback'),
            'description' => 'پرداخت از طریق زرین‌پال',
            'mode' => 'normal', // normal, zaringate
            'server' => 'iran', // iran, germany
        ],

        'zarinpal-sandbox' => [
            'apiPurchaseUrl' => 'https://sandbox.zarinpal.com/pg/rest/WebGate/PaymentRequest.json',
            'apiPaymentUrl' => 'https://sandbox.zarinpal.com/pg/StartPay/',
            'apiVerificationUrl' => 'https://sandbox.zarinpal.com/pg/rest/WebGate/PaymentVerification.json',
            'merchantId' => env('ZARINPAL_MERCHANT_ID'),
            'callbackUrl' => env('ZARINPAL_CALLBACK_URL', env('APP_URL') . '/payment/callback'),
            'description' => 'پرداخت از طریق زرین‌پال (Sandbox)',
            'mode' => 'normal', // normal, zaringate
            'server' => 'iran', // iran, germany
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Map Drivers
    |--------------------------------------------------------------------------
    |
    | This option allows you to map driver names to actual driver classes.
    | You may change these mappings as needed.
    |
    */

    'map' => [
        'zarinpal' => \Shetabit\Multipay\Drivers\Zarinpal\Zarinpal::class,
        'zarinpal-sandbox' => \Shetabit\Multipay\Drivers\Zarinpal\Zarinpal::class,
    ],
];

