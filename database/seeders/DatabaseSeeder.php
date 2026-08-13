<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RoleSeeder::class, PlatformSeeder::class, PricingPlanSeeder::class]);

        // Optional tenant scaffold for local exploration (no login passwords published).
        // Uses config() so it survives `php artisan config:cache`.
        if (! app()->environment('production') && config('app.seed_demo_tenant')) {
            $this->call(DemoTenantSeeder::class);
        }
    }
}
