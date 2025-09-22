<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SEPA QR Code Configuration
    |--------------------------------------------------------------------------
    |
    | Default bank details for SEPA QR code generation.
    | You can customize these values or make them configurable per user/company.
    |
    */

    'default_bank_details' => [
        'iban' => env('SEPA_IBAN', 'SI56 0203 1367 1566 113'),
        'bic' => env('SEPA_BIC', 'LJBASI2X'),
        'name' => env('SEPA_COMPANY_NAME', 'Test Company d.o.o.'),
        'address' => env('SEPA_COMPANY_ADDRESS', 'Your Actual Company Address'),
        'city' => env('SEPA_COMPANY_CITY', 'Ljubljana'),
        'postal_code' => env('SEPA_COMPANY_POSTAL_CODE', '1000'),
        'country' => env('SEPA_COMPANY_COUNTRY', 'SI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | QR Code Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for QR code generation
    |
    */

    'qr_code' => [
        'size' => 300,
        'margin' => 2,
        'download_size' => 400,
        'download_margin' => 3,
    ],
];
