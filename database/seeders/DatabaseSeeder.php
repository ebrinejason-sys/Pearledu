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
        // Do not re-run db:seed on a live school database to "fix" data — seeders are
        // idempotent for roles/platform admin, but they will not delete schools.
        if (! app()->environment('production') && config('app.seed_demo_tenant')) {
            $this->call(DemoTenantSeeder::class);
        }
    }
}
