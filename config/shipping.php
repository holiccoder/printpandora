<?php

return [
    'currency' => env('SHIPPING_CURRENCY', 'USD'),

    // The imported carrier tables are priced in RMB and are currently
    // applied to a 1 kg parcel. Keep the storefront currency configurable;
    // the values below are the imported rate amounts used by checkout.
    'rate_basis_weight_kg' => (float) env('SHIPPING_RATE_BASIS_WEIGHT_KG', 1),
    'package_weight_grams' => (int) env('SHIPPING_PACKAGE_WEIGHT_GRAMS', 250),
    // QC carrier prices are quoted in RMB and converted to the storefront
    // currency (USD by default) using this configurable rate.
    'rmb_to_usd_rate' => (float) env('SHIPPING_RMB_TO_USD_RATE', 0.14),
    'default_country' => env('SHIPPING_DEFAULT_COUNTRY', 'US'),

    // Product slugs used when the calculator can use the catalog's material
    // weight. The remaining category keys use conservative estimate weights
    // because those static landing pages do not have catalog products yet.
    'calculator_product_slugs' => [
        'business-cards' => 'classic-standard-business-cards',
        'cotton-business-cards' => 'basic-cotton-business-card',
        'pvc-business-cards' => 'standard-pvc-card',
    ],
    'calculator_unit_weights_grams' => [
        'business-cards' => 1.5,
        'cotton-business-cards' => 2.6,
        'pvc-business-cards' => 2.0,
        'postcards' => 5.0,
        'stickers-labels' => 1.0,
        'flyers' => 2.0,
    ],

    'default_method' => env('SHIPPING_DEFAULT_METHOD', 'standard'),

    'methods' => [
        'standard' => [
            'label' => env('SHIPPING_STANDARD_LABEL', 'Standard Shipping'),
            'carrier' => env('SHIPPING_STANDARD_CARRIER', '4PX'),
            'fee' => (float) env('SHIPPING_STANDARD_RATE', 142),
            'rate_currency' => 'RMB',
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
            // Source: docs/4PX-shipping/4PX联邮通价格表-20260828.xlsx,
            // sheet “联邮通标准挂号-普货（QC）”. Values are RMB.
            // Each QC weight tier is a fixed total: freight + registration.
            // The tier rate must not be multiplied by the actual weight.
            'weight_tiers' => [
                'US' => [
                    ['max_weight_kg' => 0.1, 'rate_rmb' => 166.0],
                    ['max_weight_kg' => 0.2, 'rate_rmb' => 159.0],
                    ['max_weight_kg' => 0.45, 'rate_rmb' => 151.0],
                    ['max_weight_kg' => 0.7, 'rate_rmb' => 149.0],
                    ['max_weight_kg' => 1.0, 'rate_rmb' => 142.0],
                    ['max_weight_kg' => 3.0, 'rate_rmb' => 142.0],
                    ['max_weight_kg' => 6.0, 'rate_rmb' => 142.0],
                    ['max_weight_kg' => 30.0, 'rate_rmb' => 142.0],
                ],
                'CA' => [
                    ['max_weight_kg' => 0.15, 'rate_rmb' => 110.0],
                    ['max_weight_kg' => 0.3, 'rate_rmb' => 111.0],
                    ['max_weight_kg' => 0.45, 'rate_rmb' => 111.0],
                    ['max_weight_kg' => 0.75, 'rate_rmb' => 113.0],
                    ['max_weight_kg' => 1.0, 'rate_rmb' => 114.0],
                    ['max_weight_kg' => 1.5, 'rate_rmb' => 115.0],
                    ['max_weight_kg' => 2.0, 'rate_rmb' => 115.0],
                    ['max_weight_kg' => 30.0, 'rate_rmb' => 119.0],
                ],
                // The storefront currently has one AU code. Keep its
                // existing zone-1 mapping until postal-zone selection exists.
                'AU' => [
                    ['max_weight_kg' => 0.3, 'rate_rmb' => 63.0],
                    ['max_weight_kg' => 0.5, 'rate_rmb' => 66.0],
                    ['max_weight_kg' => 1.0, 'rate_rmb' => 67.0],
                    ['max_weight_kg' => 3.0, 'rate_rmb' => 75.0],
                    ['max_weight_kg' => 20.0, 'rate_rmb' => 90.0],
                ],
                'NZ' => [
                    ['max_weight_kg' => 0.5, 'rate_rmb' => 110.0],
                    ['max_weight_kg' => 2.0, 'rate_rmb' => 106.0],
                    ['max_weight_kg' => 25.0, 'rate_rmb' => 99.0],
                ],
                'GB' => [
                    ['max_weight_kg' => 2.0, 'rate_rmb' => 90.0],
                    ['max_weight_kg' => 20.0, 'rate_rmb' => 90.0],
                ],
                'DE' => [
                    ['max_weight_kg' => 0.3, 'rate_rmb' => 124.0],
                    ['max_weight_kg' => 2.0, 'rate_rmb' => 124.0],
                    ['max_weight_kg' => 5.0, 'rate_rmb' => 124.0],
                    ['max_weight_kg' => 30.0, 'rate_rmb' => 129.0],
                ],
                'FR' => [
                    ['max_weight_kg' => 0.5, 'rate_rmb' => 111.0],
                    ['max_weight_kg' => 3.0, 'rate_rmb' => 115.0],
                    ['max_weight_kg' => 30.0, 'rate_rmb' => 115.0],
                ],
                'MX' => [
                    ['max_weight_kg' => 0.25, 'rate_rmb' => 119.0],
                    ['max_weight_kg' => 0.5, 'rate_rmb' => 119.0],
                    ['max_weight_kg' => 1.0, 'rate_rmb' => 119.0],
                    ['max_weight_kg' => 1.5, 'rate_rmb' => 128.0],
                    ['max_weight_kg' => 2.0, 'rate_rmb' => 128.0],
                    ['max_weight_kg' => 10.0, 'rate_rmb' => 128.0],
                ],
                'JP' => [
                    ['max_weight_kg' => 2.0, 'rate_rmb' => 38.0],
                    ['max_weight_kg' => 20.0, 'rate_rmb' => 43.0],
                ],
            ],
        ],
        'dhl_express' => [
            'label' => env('SHIPPING_DHL_LABEL', 'Express Shipping (DHL Express)'),
            'carrier' => 'DHL',
            'fee' => (float) env('SHIPPING_DHL_RATE', 201),
            'rate_currency' => 'RMB',
            // The admin shipping setting in site_settings is the source of
            // truth. This value is only used until that database setting is
            // saved or when the settings table is unavailable.
            'fuel_surcharge_percent' => 42,
            'weight_rounding_kg' => 0.5,
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
            // China export to the United States is zone 6 in
            // docs/dhl-shipping/分区表PTZONE2022.pdf. These are the
            // “0.5 KG以上包裹” zone-6 prices from 锐茨价目表.pdf, in RMB.
            'weight_tiers' => [
                'US' => [
                    ['max_weight_kg' => 0.5, 'rate_rmb' => 151.50],
                    ['max_weight_kg' => 1.0, 'rate_rmb' => 201.00],
                    ['max_weight_kg' => 1.5, 'rate_rmb' => 250.50],
                    ['max_weight_kg' => 2.0, 'rate_rmb' => 300.00],
                    ['max_weight_kg' => 2.5, 'rate_rmb' => 349.20],
                    ['max_weight_kg' => 3.0, 'rate_rmb' => 396.90],
                    ['max_weight_kg' => 3.5, 'rate_rmb' => 444.60],
                    ['max_weight_kg' => 4.0, 'rate_rmb' => 492.30],
                    ['max_weight_kg' => 4.5, 'rate_rmb' => 540.00],
                    ['max_weight_kg' => 5.0, 'rate_rmb' => 587.70],
                    ['max_weight_kg' => 5.5, 'rate_rmb' => 635.40],
                    ['max_weight_kg' => 6.0, 'rate_rmb' => 683.10],
                    ['max_weight_kg' => 6.5, 'rate_rmb' => 730.80],
                    ['max_weight_kg' => 7.0, 'rate_rmb' => 778.50],
                    ['max_weight_kg' => 7.5, 'rate_rmb' => 826.20],
                    ['max_weight_kg' => 8.0, 'rate_rmb' => 873.90],
                    ['max_weight_kg' => 8.5, 'rate_rmb' => 921.60],
                    ['max_weight_kg' => 9.0, 'rate_rmb' => 969.30],
                    ['max_weight_kg' => 9.5, 'rate_rmb' => 1017.00],
                    ['max_weight_kg' => 10.0, 'rate_rmb' => 1064.70],
                    ['max_weight_kg' => 10.5, 'rate_rmb' => 1109.70],
                    ['max_weight_kg' => 11.0, 'rate_rmb' => 1154.70],
                    ['max_weight_kg' => 11.5, 'rate_rmb' => 1199.70],
                    ['max_weight_kg' => 12.0, 'rate_rmb' => 1244.70],
                    ['max_weight_kg' => 12.5, 'rate_rmb' => 1289.70],
                    ['max_weight_kg' => 13.0, 'rate_rmb' => 1334.70],
                    ['max_weight_kg' => 13.5, 'rate_rmb' => 1379.70],
                    ['max_weight_kg' => 14.0, 'rate_rmb' => 1424.70],
                    ['max_weight_kg' => 14.5, 'rate_rmb' => 1469.70],
                    ['max_weight_kg' => 15.0, 'rate_rmb' => 1514.70],
                    ['max_weight_kg' => 15.5, 'rate_rmb' => 1559.70],
                    ['max_weight_kg' => 16.0, 'rate_rmb' => 1604.70],
                    ['max_weight_kg' => 16.5, 'rate_rmb' => 1649.70],
                    ['max_weight_kg' => 17.0, 'rate_rmb' => 1694.70],
                    ['max_weight_kg' => 17.5, 'rate_rmb' => 1739.70],
                    ['max_weight_kg' => 18.0, 'rate_rmb' => 1784.70],
                    ['max_weight_kg' => 18.5, 'rate_rmb' => 1829.70],
                    ['max_weight_kg' => 19.0, 'rate_rmb' => 1874.70],
                    ['max_weight_kg' => 19.5, 'rate_rmb' => 1919.70],
                    ['max_weight_kg' => 20.0, 'rate_rmb' => 1964.70],
                    ['max_weight_kg' => 20.5, 'rate_rmb' => 1263.88],
                    ['max_weight_kg' => 21.0, 'rate_rmb' => 1283.45],
                    ['max_weight_kg' => 21.5, 'rate_rmb' => 1303.02],
                    ['max_weight_kg' => 22.0, 'rate_rmb' => 1322.59],
                    ['max_weight_kg' => 22.5, 'rate_rmb' => 1342.16],
                    ['max_weight_kg' => 23.0, 'rate_rmb' => 1361.73],
                    ['max_weight_kg' => 23.5, 'rate_rmb' => 1381.30],
                    ['max_weight_kg' => 24.0, 'rate_rmb' => 1400.87],
                    ['max_weight_kg' => 24.5, 'rate_rmb' => 1420.44],
                    ['max_weight_kg' => 25.0, 'rate_rmb' => 1440.01],
                    ['max_weight_kg' => 25.5, 'rate_rmb' => 1459.58],
                    ['max_weight_kg' => 26.0, 'rate_rmb' => 1479.15],
                    ['max_weight_kg' => 26.5, 'rate_rmb' => 1498.72],
                    ['max_weight_kg' => 27.0, 'rate_rmb' => 1518.29],
                    ['max_weight_kg' => 27.5, 'rate_rmb' => 1537.86],
                    ['max_weight_kg' => 28.0, 'rate_rmb' => 1557.43],
                    ['max_weight_kg' => 28.5, 'rate_rmb' => 1577.00],
                    ['max_weight_kg' => 29.0, 'rate_rmb' => 1596.57],
                    ['max_weight_kg' => 29.5, 'rate_rmb' => 1616.14],
                    ['max_weight_kg' => 30.0, 'rate_rmb' => 1635.71],
                    // From 30.1kg onward the PDF switches to a per-kg rate;
                    // the chargeable weight is rounded up to a whole kg.
                    ['min_weight_kg' => 30.1, 'max_weight_kg' => 70.0, 'weight_rounding_kg' => 1.0, 'freight_rmb_per_kg' => 55.80],
                    ['min_weight_kg' => 70.1, 'max_weight_kg' => 300.0, 'weight_rounding_kg' => 1.0, 'freight_rmb_per_kg' => 58.59],
                    ['min_weight_kg' => 300.1, 'max_weight_kg' => 99999.0, 'weight_rounding_kg' => 1.0, 'freight_rmb_per_kg' => 62.20],
                ],
            ],
        ],
    ],
];
