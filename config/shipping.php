<?php

return [
    'currency' => env('SHIPPING_CURRENCY', 'USD'),

    'default_method' => env('SHIPPING_DEFAULT_METHOD', 'standard'),

    'methods' => [
        'standard' => [
            'label' => env('SHIPPING_STANDARD_LABEL', 'Standard Shipping'),
            'carrier' => env('SHIPPING_STANDARD_CARRIER', '4PX'),
            'fee' => (float) env('SHIPPING_STANDARD_RATE', 5.99),
            'description' => env('SHIPPING_STANDARD_DESCRIPTION', '4PX tracked delivery. Shipment is created after payment.'),
            'estimated_delivery' => env('SHIPPING_STANDARD_ESTIMATE', '5-10 business days'),
        ],
        'dhl_express' => [
            'label' => env('SHIPPING_DHL_LABEL', 'Fast Shipping (DHL Express)'),
            'carrier' => 'DHL',
            'fee' => (float) env('SHIPPING_DHL_RATE', 14.99),
            'description' => env('SHIPPING_DHL_DESCRIPTION', 'Fast tracked delivery. DHL shipment will be created manually after payment.'),
            'estimated_delivery' => env('SHIPPING_DHL_ESTIMATE', '2-5 business days'),
        ],
    ],
];
