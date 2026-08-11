<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SchoolPay API base URL
    |--------------------------------------------------------------------------
    |
    | Production: https://schoolpay.co.ug/paymentapi
    | UAT/test:   https://schoolpaytest.servicecops.com/uatpaymentapi
    |
    | Per-school credentials (school code + API password) live on the schools
    | table — never put a shared password here.
    |
    */

    'base_url' => rtrim((string) env('SCHOOLPAY_BASE_URL', 'https://schoolpay.co.ug/paymentapi'), '/'),

    'timeout' => (int) env('SCHOOLPAY_TIMEOUT', 20),

    /*
    | When true, parent portal can initiate SchoolPay adhoc MoMo requests.
    | Schools still must enable SchoolPay and save credentials in settings.
    */
    'adhoc_enabled' => (bool) env('SCHOOLPAY_ADHOC_ENABLED', true),

    /*
    | Daily sync looks back this many days (inclusive of today, max 31 via API).
    */
    'sync_lookback_days' => (int) env('SCHOOLPAY_SYNC_LOOKBACK_DAYS', 2),

];
