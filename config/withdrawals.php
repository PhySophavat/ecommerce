<?php

return [
    'minimum_amount' => (float) env('WITHDRAWAL_MINIMUM_AMOUNT', 10),
    'fixed_fee' => (float) env('WITHDRAWAL_FIXED_FEE', 0),
    'banks' => [
        'ABA',
        'ACLEDA',
        'Wing',
        'TrueMoney',
        'Pi Pay',
    ],
];
