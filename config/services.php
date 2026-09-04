<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paypal' => [
        // 'sandbox' or 'live'
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'currency' => env('PAYPAL_CURRENCY', 'USD'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    'cryptomus' => [
        'merchant_uuid' => env('CRYPTOMUS_MERCHANT_UUID'),
        'payment_key' => env('CRYPTOMUS_PAYMENT_KEY'),
        'payout_key' => env('CRYPTOMUS_PAYOUT_KEY'),
        'currency' => env('CRYPTOMUS_CURRENCY', 'USD'),
        'test' => env('CRYPTOMUS_TEST', true),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 10),
        'support_chat_id' => env('TELEGRAM_SUPPORT_CHAT_ID'),
        'support_user_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TELEGRAM_SUPPORT_USER_IDS', '')),
        ))),
    ],

    'wecom' => [
        'corp_id' => env('WECOM_CORP_ID'),
        'kf_secret' => env('WECOM_KF_SECRET'),
        'callback_token' => env('WECOM_CALLBACK_TOKEN'),
        'encoding_aes_key' => env('WECOM_ENCODING_AES_KEY'),
        'open_kfid' => env('WECOM_OPEN_KFID'),
        'timeout' => (int) env('WECOM_TIMEOUT', 10),

        // Self-built application that bridges website support chat to staff.
        // Its callback credentials fall back to the customer-service ones so a
        // single Token/EncodingAESKey pair can be reused in the admin console.
        'app_agent_id' => env('WECOM_APP_AGENT_ID'),
        'app_secret' => env('WECOM_APP_SECRET'),
        'app_support_user_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('WECOM_APP_SUPPORT_USER_IDS', '')),
        ))),
        'app_callback_token' => env('WECOM_APP_CALLBACK_TOKEN') ?: env('WECOM_CALLBACK_TOKEN'),
        'app_encoding_aes_key' => env('WECOM_APP_ENCODING_AES_KEY') ?: env('WECOM_ENCODING_AES_KEY'),
    ],

    'feishu' => [
        'app_id' => env('FEISHU_APP_ID'),
        'app_secret' => env('FEISHU_APP_SECRET'),
        'verification_token' => env('FEISHU_VERIFICATION_TOKEN'),
        'encrypt_key' => env('FEISHU_ENCRYPT_KEY'),
        'timeout' => (int) env('FEISHU_TIMEOUT', 10),
        'base_url' => env('FEISHU_BASE_URL', 'https://open.feishu.cn/open-apis'),
        'support_open_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('FEISHU_SUPPORT_OPEN_IDS', '')),
        ))),
    ],

    'four_px' => [
        'enabled' => (bool) env('FOURPX_ENABLED', false),
        'environment' => env('FOURPX_ENVIRONMENT', 'test'),
        'base_url' => env('FOURPX_BASE_URL', 'https://open.4px.com/router/api/service'),
        'test_base_url' => env('FOURPX_TEST_BASE_URL', 'https://open-test.4px.com/router/api/service'),
        'app_key' => env('FOURPX_APP_KEY'),
        'app_secret' => env('FOURPX_APP_SECRET'),
        'access_token' => env('FOURPX_ACCESS_TOKEN'),
        'language' => env('FOURPX_LANGUAGE', 'en'),
        'order_version' => env('FOURPX_ORDER_VERSION', '1.1'),
        'query_version' => env('FOURPX_QUERY_VERSION', '1.1'),
        'label_version' => env('FOURPX_LABEL_VERSION', '1.1'),
        'tracking_version' => env('FOURPX_TRACKING_VERSION', '1.0'),
        'reference_prefix' => env('FOURPX_REFERENCE_PREFIX', 'PRINTPANDORA-'),
        'logistics_product_code' => env('FOURPX_LOGISTICS_PRODUCT_CODE'),
        'business_type' => env('FOURPX_BUSINESS_TYPE', 'BDS'),
        'duty_type' => env('FOURPX_DUTY_TYPE', 'U'),
        'cargo_type' => env('FOURPX_CARGO_TYPE', '5'),
        'customs_service' => env('FOURPX_CUSTOMS_SERVICE', 'N'),
        'signature_service' => env('FOURPX_SIGNATURE_SERVICE', 'N'),
        'value_added_services' => env('FOURPX_VALUE_ADDED_SERVICES', ''),
        'include_battery' => env('FOURPX_INCLUDE_BATTERY', 'N'),
        'battery_type' => env('FOURPX_BATTERY_TYPE'),
        'declare_currency' => env('FOURPX_DECLARE_CURRENCY', 'USD'),
        'origin_country' => env('FOURPX_ORIGIN_COUNTRY', 'CN'),
        'default_weight_grams' => env('FOURPX_DEFAULT_WEIGHT_GRAMS'),
        'default_length_cm' => env('FOURPX_DEFAULT_LENGTH_CM'),
        'default_width_cm' => env('FOURPX_DEFAULT_WIDTH_CM'),
        'default_height_cm' => env('FOURPX_DEFAULT_HEIGHT_CM'),
        'deliver_type' => env('FOURPX_DELIVER_TYPE', '3'),
        'warehouse_code' => env('FOURPX_WAREHOUSE_CODE'),
        'return_domestic' => env('FOURPX_RETURN_DOMESTIC', 'N'),
        'return_overseas' => env('FOURPX_RETURN_OVERSEAS', 'N'),
        'return_domestic_address' => [
            'first_name' => env('FOURPX_RETURN_DOMESTIC_FIRST_NAME'),
            'last_name' => env('FOURPX_RETURN_DOMESTIC_LAST_NAME'),
            'company' => env('FOURPX_RETURN_DOMESTIC_COMPANY'),
            'phone' => env('FOURPX_RETURN_DOMESTIC_PHONE'),
            'email' => env('FOURPX_RETURN_DOMESTIC_EMAIL'),
            'post_code' => env('FOURPX_RETURN_DOMESTIC_POST_CODE'),
            'country' => env('FOURPX_RETURN_DOMESTIC_COUNTRY', 'CN'),
            'state' => env('FOURPX_RETURN_DOMESTIC_STATE'),
            'city' => env('FOURPX_RETURN_DOMESTIC_CITY'),
            'district' => env('FOURPX_RETURN_DOMESTIC_DISTRICT'),
            'street' => env('FOURPX_RETURN_DOMESTIC_STREET'),
            'house_number' => env('FOURPX_RETURN_DOMESTIC_HOUSE_NUMBER'),
        ],
        'return_overseas_address' => [
            'first_name' => env('FOURPX_RETURN_OVERSEAS_FIRST_NAME'),
            'last_name' => env('FOURPX_RETURN_OVERSEAS_LAST_NAME'),
            'company' => env('FOURPX_RETURN_OVERSEAS_COMPANY'),
            'phone' => env('FOURPX_RETURN_OVERSEAS_PHONE'),
            'email' => env('FOURPX_RETURN_OVERSEAS_EMAIL'),
            'post_code' => env('FOURPX_RETURN_OVERSEAS_POST_CODE'),
            'country' => env('FOURPX_RETURN_OVERSEAS_COUNTRY'),
            'state' => env('FOURPX_RETURN_OVERSEAS_STATE'),
            'city' => env('FOURPX_RETURN_OVERSEAS_CITY'),
            'district' => env('FOURPX_RETURN_OVERSEAS_DISTRICT'),
            'street' => env('FOURPX_RETURN_OVERSEAS_STREET'),
            'house_number' => env('FOURPX_RETURN_OVERSEAS_HOUSE_NUMBER'),
        ],
        'request_label' => (bool) env('FOURPX_REQUEST_LABEL', false),
        'tracking_url_template' => env('FOURPX_TRACKING_URL_TEMPLATE'),
        'timeout' => (int) env('FOURPX_TIMEOUT', 30),
        'sender' => [
            'first_name' => env('FOURPX_SENDER_FIRST_NAME'),
            'last_name' => env('FOURPX_SENDER_LAST_NAME'),
            'company' => env('FOURPX_SENDER_COMPANY'),
            'phone' => env('FOURPX_SENDER_PHONE'),
            'email' => env('FOURPX_SENDER_EMAIL'),
            'post_code' => env('FOURPX_SENDER_POST_CODE'),
            'country' => env('FOURPX_SENDER_COUNTRY', 'CN'),
            'state' => env('FOURPX_SENDER_STATE'),
            'city' => env('FOURPX_SENDER_CITY'),
            'district' => env('FOURPX_SENDER_DISTRICT'),
            'street' => env('FOURPX_SENDER_STREET'),
            'house_number' => env('FOURPX_SENDER_HOUSE_NUMBER'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

];
