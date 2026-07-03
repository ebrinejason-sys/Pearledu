<?php
namespace Database\Seeders;
use App\Models\PricingPlan;
use Illuminate\Database\Seeder;

/** Starter tiers so the PearlEdu landing pricing section is never empty. */
class PricingPlanSeeder extends Seeder {
    public function run(): void {
        $plans = [
            [
                'name' => 'Starter',
                'tagline' => 'For small schools getting organised',
                'price' => null,
                'billing_period' => 'per term',
                'features' => [
                    'Up to 300 students',
                    'Attendance tracking',
                    'Grading & report cards',
                    'Email support',
                ],
                'is_highlighted' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Standard',
                'tagline' => 'For growing schools that need it all',
                'price' => null,
                'billing_period' => 'per term',
                'features' => [
                    'Unlimited students',
                    'Everything in Starter',
                    'Fees & mobile money payments',
                    'Parent SMS communication',
                    'Priority support',
                ],
                'is_highlighted' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'tagline' => 'For school groups and districts',
                'price' => null,
                'billing_period' => 'per term',
                'features' => [
                    'Everything in Standard',
                    'Multiple campuses',
                    'Custom onboarding & training',
                    'Dedicated account manager',
                ],
                'is_highlighted' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            PricingPlan::firstOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
