<?php
return [
    'base_domain' => env('TENANCY_BASE_DOMAIN', 'voxsign.co.ug'),
    'platform_subdomains' => ['pearledu', 'app', 'admin', 'platform'],
    'landing_hosts' => array_filter(explode(',', env('TENANCY_LANDING_HOSTS', 'voxsign.co.ug,www.voxsign.co.ug'))),
    'pearledu_landing_host' => env('TENANCY_PEARLEDU_LANDING_HOST', 'pearledu.'.env('TENANCY_BASE_DOMAIN', 'voxsign.co.ug')),
    'tenant_slug_prefix' => env('TENANCY_TENANT_PREFIX', 'pearledu'),
    'reserved_subdomains' => [
        'pearledu','app','www','api','admin','mail','smtp','imap','ftp','platform',
        'dashboard','status','support','help','docs','static','assets','cdn','mx','ns1','ns2','root','voxsign',
    ],
];
