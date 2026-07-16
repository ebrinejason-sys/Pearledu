<?php

return [
    'admin_name' => env('PLATFORM_ADMIN_NAME', 'Platform Admin'),
    'admin_email' => env('PLATFORM_ADMIN_EMAIL', 'admin@voxsign.co.ug'),
    // Never default a password — PlatformSeeder refuses an empty value.
    'admin_password' => env('PLATFORM_ADMIN_PASSWORD'),
];
