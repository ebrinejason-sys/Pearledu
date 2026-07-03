<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([RoleSeeder::class, PlatformSeeder::class, PricingPlanSeeder::class, DemoTenantSeeder::class]);
    }
}
