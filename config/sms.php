<?php
return [
    // fake = no delivery (local/tests); log = write outbound SMS to the app log;
    // any other driver fails closed until a real gateway is implemented.
    'driver' => env('SMS_DRIVER', 'fake'),
    'chars_per_segment' => 160,
];
