<?php

namespace Tests;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        $this->travelBack();

        if ($this->app?->bound(TenantContext::class)) {
            try {
                $this->app->make(TenantContext::class)->clear();
            } catch (Throwable) {
                // Unit tests may never open a database connection.
            }
        }

        parent::tearDown();
    }

    /**
     * Attach a user to an active school so /login can complete (school users
     * without a membership are rejected).
     *
     * @param  array<string, mixed>  $userAttributes
     */
    protected function makeSchoolUser(array $userAttributes = [], string $roleKey = Role::SUBJECT_TEACHER): User
    {
        if (! Role::query()->where('key', $roleKey)->exists()) {
            $this->seed(RoleSeeder::class);
        }

        $context = app(TenantContext::class);
        $context->forPlatform();

        $school = School::query()->where('slug', 'ci-login-school')->first()
            ?? School::create([
                'name' => 'CI Login School',
                'slug' => 'ci-login-school',
                'status' => 'active',
            ]);

        $user = User::factory()->create(array_merge([
            'status' => 'active',
        ], $userAttributes));

        RoleAssignment::firstOrCreate([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('key', $roleKey)->value('id'),
            'school_id' => $school->id,
        ], [
            'is_active' => true,
        ]);

        return $user;
    }

    /**
     * Flatten sidebar labels including nested groups (Learners → View Learners).
     *
     * @param  array<string, mixed>  $nav
     * @return list<string>
     */
    protected function navLabels(array $nav): array
    {
        $labels = [];
        foreach ($nav['sections'] ?? [] as $section) {
            foreach ($section['items'] ?? [] as $item) {
                if (! empty($item['label'])) {
                    $labels[] = $item['label'];
                }
                foreach ($item['children'] ?? [] as $child) {
                    if (! empty($child['label'])) {
                        $labels[] = $child['label'];
                    }
                }
            }
        }

        return $labels;
    }
}
