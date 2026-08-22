<?php

namespace Tests\Feature;

use App\Console\Commands\VerifyDatabaseSecurity;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VerifyDatabaseSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires PostgreSQL.');
        }
    }

    public function test_catalog_discovers_school_scoped_tables_including_mis_modules(): void
    {
        $this->seed();

        $tables = app(VerifyDatabaseSecurity::class)->schoolScopedTables();

        $this->assertContains('schools', $tables);
        $this->assertContains('students', $tables);
        $this->assertContains('marks', $tables);
        $this->assertContains('helpdesk_tickets', $tables);
        $this->assertContains('lms_submissions', $tables);
        $this->assertContains('cbt_attempts', $tables);
        $this->assertContains('staff_documents', $tables);
        $this->assertGreaterThan(30, count($tables));
    }

    public function test_verify_security_passes_after_migrations(): void
    {
        $this->seed();

        $exit = Artisan::call('db:verify-security');

        $this->assertSame(0, $exit, Artisan::output());
    }

    public function test_composite_tenant_fk_rejects_cross_school_student_on_marks(): void
    {
        $this->seed();

        $schoolA = DB::table('schools')->where('slug', 'like', 'pearledu%')->value('id');
        app(TenantContext::class)->forPlatform();

        $schoolB = DB::table('schools')->insertGetId([
            'name' => 'Other School',
            'slug' => 'pearledu-fk-'.uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classA = DB::table('school_classes')->insertGetId([
            'school_id' => $schoolA,
            'level' => 'primary',
            'name' => 'FK-A',
            'code' => 'FKA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subjectA = DB::table('subjects')->insertGetId([
            'school_id' => $schoolA,
            'name' => 'FK Math',
            'code' => 'FKM',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $periodA = DB::table('assessment_periods')->insertGetId([
            'school_id' => $schoolA,
            'name' => 'FK Period',
            'max_score' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $studentB = DB::table('students')->insertGetId([
            'school_id' => $schoolB,
            'full_name' => 'Foreign Student',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('marks')->insert([
            'school_id' => $schoolA,
            'assessment_period_id' => $periodA,
            'student_id' => $studentB,
            'subject_id' => $subjectA,
            'class_id' => $classA,
            'score' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
