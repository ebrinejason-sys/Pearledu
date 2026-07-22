<?php
return [
    // fake = no delivery (local/tests); log = write outbound SMS to the app log;
    // twilio = real delivery via Twilio; any other driver fails closed until
    // a real gateway is implemented.
    'driver' => env('SMS_DRIVER', 'fake'),
    'chars_per_segment' => 160,

    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM_NUMBER'),
    ],
];
