<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Teaching assignments attach to staff (user_id) with academic period scope,
 * not to a specific role_assignments row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_assignments', function (Blueprint $t) {
            $t->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('academic_year_id')->nullable()->after('school_id')->constrained('academic_years')->cascadeOnDelete();
            $t->foreignId('term_id')->nullable()->after('academic_year_id')->constrained('terms')->nullOnDelete();
            $t->date('starts_on')->nullable()->after('class_id');
            $t->date('ends_on')->nullable()->after('starts_on');
            $t->string('status', 20)->default('active')->after('ends_on');
        });

        // Backfill teacher identity from the linked role assignment.
        DB::statement('
            UPDATE teaching_assignments AS ta
            SET user_id = ra.user_id
            FROM role_assignments AS ra
            WHERE ta.assignment_id = ra.id
              AND ta.user_id IS NULL
        ');

        // Attach to each school's current (or latest) academic year.
        $schools = DB::table('teaching_assignments')
            ->whereNull('academic_year_id')
            ->distinct()
            ->pluck('school_id');

        foreach ($schools as $schoolId) {
            $yearId = DB::table('academic_years')
                ->where('school_id', $schoolId)
                ->where('is_current', true)
                ->value('id');

            if (! $yearId) {
                $yearId = DB::table('academic_years')
                    ->where('school_id', $schoolId)
                    ->orderByDesc('starts_on')
                    ->value('id');
            }

            if (! $yearId) {
                $yearId = DB::table('academic_years')->insertGetId([
                    'school_id' => $schoolId,
                    'name' => (string) now()->year,
                    'starts_on' => now()->startOfYear()->toDateString(),
                    'ends_on' => now()->endOfYear()->toDateString(),
                    'is_current' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('teaching_assignments')
                ->where('school_id', $schoolId)
                ->whereNull('academic_year_id')
                ->update(['academic_year_id' => $yearId]);
        }

        // Remove orphaned rows that could not be linked to a staff member.
        DB::table('teaching_assignments')->whereNull('user_id')->delete();
        DB::table('teaching_assignments')->whereNull('academic_year_id')->delete();

        // Drop legacy unique + FK before removing assignment_id.
        Schema::table('teaching_assignments', function (Blueprint $t) {
            $t->dropUnique(['assignment_id', 'subject_id', 'class_id']);
            $t->dropConstrainedForeignId('assignment_id');
        });

        DB::statement('ALTER TABLE teaching_assignments ALTER COLUMN user_id SET NOT NULL');
        DB::statement('ALTER TABLE teaching_assignments ALTER COLUMN academic_year_id SET NOT NULL');

        // Unique per teacher/class/subject/year/term (NULL term = whole year).
        DB::statement('
            CREATE UNIQUE INDEX teaching_assignments_teacher_scope_unique
            ON teaching_assignments (
                school_id,
                user_id,
                class_id,
                subject_id,
                academic_year_id,
                COALESCE(term_id, 0)
            )
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS teaching_assignments_teacher_scope_unique');

        Schema::table('teaching_assignments', function (Blueprint $t) {
            $t->foreignId('assignment_id')->nullable()->after('id')->constrained('role_assignments')->cascadeOnDelete();
        });

        // Best-effort restore: first active role assignment for that user in the school.
        DB::statement('
            UPDATE teaching_assignments AS ta
            SET assignment_id = (
                SELECT ra.id
                FROM role_assignments AS ra
                WHERE ra.user_id = ta.user_id
                  AND ra.school_id = ta.school_id
                  AND ra.is_active = true
                ORDER BY ra.id
                LIMIT 1
            )
            WHERE ta.assignment_id IS NULL
        ');

        Schema::table('teaching_assignments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('term_id');
            $t->dropConstrainedForeignId('academic_year_id');
            $t->dropConstrainedForeignId('user_id');
            $t->dropColumn(['starts_on', 'ends_on', 'status']);
            $t->unique(['assignment_id', 'subject_id', 'class_id']);
        });
    }
};
