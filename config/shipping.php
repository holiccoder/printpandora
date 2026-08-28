<?php

return [
    'currency' => env('SHIPPING_CURRENCY', 'USD'),

    // The imported carrier tables are priced in RMB and are currently
    // applied to a 1 kg parcel. Keep the storefront currency configurable;
    // the values below are the imported rate amounts used by checkout.
    'rate_basis_weight_kg' => (float) env('SHIPPING_RATE_BASIS_WEIGHT_KG', 1),
    'default_country' => env('SHIPPING_DEFAULT_COUNTRY', 'US'),

    'default_method' => env('SHIPPING_DEFAULT_METHOD', 'standard'),

    'methods' => [
        'standard' => [
            'label' => env('SHIPPING_STANDARD_LABEL', 'Standard Shipping'),
            'carrier' => env('SHIPPING_STANDARD_CARRIER', '4PX'),
            'fee' => (float) env('SHIPPING_STANDARD_RATE', 142),
            'description' => env('SHIPPING_STANDARD_DESCRIPTION', '4PX tracked delivery. Shipment is created after payment.'),
            'estimated_delivery' => env('SHIPPING_STANDARD_ESTIMATE', '7-12 business days'),
            'max_business_days' => (int) env('SHIPPING_STANDARD_MAX_BUSINESS_DAYS', 12),
            // 4PX 联邮通标准挂号-普货（QC）价格表（2026-08-28），按 1 kg
            // 计费档位导入：运费 + 挂号费。
            'country_rates' => [
                'US' => 142.00,
                'CA' => 114.00,
                'AU' => 67.00,
                'NZ' => 106.00,
                'GB' => 90.00,
                'DE' => 124.00,
                'FR' => 115.00,
                'MX' => 119.00,
                'JP' => 38.00,
            ],
        ],
        'dhl_express' => [
            'label' => env('SHIPPING_DHL_LABEL', 'Fast Shipping (DHL Express)'),
            'carrier' => 'DHL',
            'fee' => (float) env('SHIPPING_DHL_RATE', 201),
            'description' => env('SHIPPING_DHL_DESCRIPTION', 'Fast tracked delivery. DHL shipment will be created manually after payment.'),
            'estimated_delivery' => env('SHIPPING_DHL_ESTIMATE', '2-5 business days'),
            'max_business_days' => (int) env('SHIPPING_DHL_MAX_BUSINESS_DAYS', 5),
            // DHL PTZONE2022 + 锐茨价目表：出口中国、1 kg 包裹报价。
            'country_rates' => [
                'US' => 201.00,
                'CA' => 201.00,
                'AU' => 193.50,
                'NZ' => 193.50,
                'GB' => 242.70,
                'DE' => 242.70,
                'FR' => 242.70,
                'MX' => 201.00,
                'JP' => 156.00,
            ],
        ],
    ],
];
