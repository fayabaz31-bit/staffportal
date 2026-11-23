<?php

return [
    'domain' => env('STUDENT_PORTAL_DOMAIN'),
    'remita' => [
        'merchant_id' => env('REMITA_MERCHANT_ID'),
        'service_type_id' => env('REMITA_SERVICE_TYPE_ID'),
        'api_key' => env('REMITA_API_KEY'),
        'base_url' => env('REMITA_BASE_URL', 'https://demo.remita.net'),
    ],
];
