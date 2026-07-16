<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([RoleSeeder::class, PlatformSeeder::class, PricingPlanSeeder::class]);

        // Optional tenant scaffold for local exploration (no login passwords published).
        if (! app()->environment('production') && env('SEED_DEMO_TENANT', false)) {
            $this->call(DemoTenantSeeder::class);
        }
    }
}
