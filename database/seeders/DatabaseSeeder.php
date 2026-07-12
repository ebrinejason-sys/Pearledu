<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([RoleSeeder::class, PlatformSeeder::class, PricingPlanSeeder::class]);

        // Demo school + role profiles are local/dev only — never seed into production.
        if (! app()->environment('production')) {
            $this->call(DemoTenantSeeder::class);
        }
    }
}
