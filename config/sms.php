<?php
return [
    // Default gateway driver; the platform admin can change provider in SmsSetting.
    'driver' => env('SMS_DRIVER', 'fake'),
    'chars_per_segment' => 160,
];
