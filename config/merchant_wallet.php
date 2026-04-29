<?php

return [
    'khqr_code' => env('MERCHANT_WALLET_KHQR_CODE', '00020101021129370016A0000006770101110113006688990015204581253031165406100.005802KH5910Ecommerce6009Phnom Penh6304ABCD'),
    'khqr_image_url' => env('MERCHANT_WALLET_KHQR_IMAGE_URL'),
    'recent_transactions_limit' => (int) env('MERCHANT_WALLET_RECENT_LIMIT', 8),
    'providers' => [
        'ABA' => [
            'bank_name' => 'ABA',
            'merchant_name' => 'E-commerce',
            'account_name' => 'E-commerce KHQR Collection',
            'account_number' => '001 248 555',
            'phone_number' => '010 248 555',
            'khqr_prefix' => 'KHQR-ABA',
        ],
        'ACLEDA' => [
            'bank_name' => 'ACLEDA',
            'merchant_name' => 'E-commerce',
            'account_name' => 'E-commerce ACLEDA Merchant',
            'account_number' => '5522 0099 811',
            'phone_number' => '012 552 811',
            'khqr_prefix' => 'KHQR-ACLEDA',
        ],
        'Wing' => [
            'bank_name' => 'Wing',
            'merchant_name' => 'E-commerce',
            'account_name' => 'E-commerce Wing Wallet',
            'account_number' => '933 884 120',
            'phone_number' => '088 933 4120',
            'khqr_prefix' => 'KHQR-WING',
        ],
        'TrueMoney' => [
            'bank_name' => 'TrueMoney',
            'merchant_name' => 'E-commerce',
            'account_name' => 'E-commerce TrueMoney',
            'account_number' => 'TRU 449 228',
            'phone_number' => '097 449 228',
            'khqr_prefix' => 'KHQR-TRUEMONEY',
        ],
    ],
];
