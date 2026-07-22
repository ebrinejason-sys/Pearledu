<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Enable FORCE RLS on all Sprint 1–12 school-scoped tables. */
return new class extends Migration
{
    private array $tables = [
        'academic_years', 'terms', 'enrollments', 'attendance_records',
        'assessment_periods', 'marks', 'promotion_batches', 'promotion_items',
        'timetable_periods', 'rooms', 'timetable_slots',
        'fee_structures', 'fee_invoices', 'fee_payments',
        'announcements', 'admission_applications',
        'lms_materials', 'lms_assignments', 'cbt_exams', 'cbt_questions',
        'library_books', 'library_loans', 'inventory_items',
        'transport_routes', 'hostel_rooms', 'hostel_allocations',
        'leave_requests', 'clinic_visits', 'helpdesk_tickets',
    ];

    public function up(): void
    {
        $pred = "(
            COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
            OR school_id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
        )";

        foreach ($this->tables as $t) {
            DB::statement("ALTER TABLE {$t} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$t} FORCE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY tenant_isolation ON {$t} USING {$pred} WITH CHECK {$pred}");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$t}");
            DB::statement("ALTER TABLE {$t} NO FORCE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$t} DISABLE ROW LEVEL SECURITY");
        }
    }
};
