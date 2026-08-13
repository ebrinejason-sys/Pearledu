<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $t) {
            $t->foreignId('student_id')->nullable()->after('requested_class_id')->constrained('students')->nullOnDelete();
            $t->timestamp('admitted_at')->nullable()->after('status');
        });

        Schema::table('enrollments', function (Blueprint $t) {
            $t->date('enrolled_on')->nullable()->after('status');
            $t->date('exited_on')->nullable()->after('enrolled_on');
        });

        Schema::create('grading_schemes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('kind')->default('uneb_olevel');
            $t->boolean('is_default')->default(false);
            $t->timestamps();
            $t->unique(['school_id', 'name']);
        });

        Schema::create('grading_scheme_bands', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('grading_scheme_id')->constrained('grading_schemes')->cascadeOnDelete();
            $t->decimal('min_score', 8, 2);
            $t->decimal('max_score', 8, 2);
            $t->string('grade', 16);
            $t->string('remark')->nullable();
            $t->unsignedTinyInteger('points')->nullable();
            $t->unsignedTinyInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::table('assessment_periods', function (Blueprint $t) {
            $t->string('status')->default('draft')->after('is_locked');
            $t->timestamp('published_at')->nullable()->after('status');
            $t->timestamp('locked_at')->nullable()->after('published_at');
            $t->foreignId('grading_scheme_id')->nullable()->after('locked_at')->constrained('grading_schemes')->nullOnDelete();
        });

        Schema::table('marks', function (Blueprint $t) {
            $t->unsignedTinyInteger('points')->nullable()->after('grade');
            $t->string('remark')->nullable()->after('points');
        });

        Schema::table('schools', function (Blueprint $t) {
            $t->json('enabled_modules')->nullable();
            $t->json('report_settings')->nullable();
            $t->timestamp('setup_completed_at')->nullable();
        });

        Schema::create('fee_adjustments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('invoice_id')->nullable()->constrained('fee_invoices')->nullOnDelete();
            $t->string('type'); // discount, waiver, credit, reversal
            $t->decimal('amount', 12, 2);
            $t->string('reason')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::table('fee_payments', function (Blueprint $t) {
            $t->foreignId('reverses_payment_id')->nullable()->after('status')->constrained('fee_payments')->nullOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE assessment_periods
                SET status = CASE WHEN is_locked THEN 'locked' ELSE 'mark_entry_open' END
                WHERE status = 'draft' OR status IS NULL
            ");

            DB::statement('
                UPDATE assessment_periods
                SET locked_at = COALESCE(locked_at, NOW())
                WHERE is_locked = true AND locked_at IS NULL
            ');

            // Keep one live invoice per billing identity, void extras (oldest kept).
            DB::statement("
                UPDATE fee_invoices SET status = 'void'
                WHERE id IN (
                    SELECT id FROM (
                        SELECT id,
                               ROW_NUMBER() OVER (
                                   PARTITION BY school_id, student_id, fee_structure_id
                                   ORDER BY id
                               ) AS rn
                        FROM fee_invoices
                        WHERE fee_structure_id IS NOT NULL
                          AND status <> 'void'
                    ) ranked
                    WHERE rn > 1
                )
            ");

            DB::statement('
                CREATE UNIQUE INDEX fee_invoices_billing_identity_unique
                ON fee_invoices (school_id, student_id, fee_structure_id)
                WHERE fee_structure_id IS NOT NULL AND status <> \'void\'
            ');
        }

        $this->enableRls(['grading_schemes', 'grading_scheme_bands', 'fee_adjustments']);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS fee_invoices_billing_identity_unique');
        }

        Schema::table('fee_payments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('reverses_payment_id');
        });

        Schema::dropIfExists('fee_adjustments');

        Schema::table('schools', function (Blueprint $t) {
            $t->dropColumn(['enabled_modules', 'report_settings', 'setup_completed_at']);
        });

        Schema::table('marks', function (Blueprint $t) {
            $t->dropColumn(['points', 'remark']);
        });

        Schema::table('assessment_periods', function (Blueprint $t) {
            $t->dropConstrainedForeignId('grading_scheme_id');
            $t->dropColumn(['status', 'published_at', 'locked_at']);
        });

        Schema::dropIfExists('grading_scheme_bands');
        Schema::dropIfExists('grading_schemes');

        Schema::table('enrollments', function (Blueprint $t) {
            $t->dropColumn(['enrolled_on', 'exited_on']);
        });

        Schema::table('admission_applications', function (Blueprint $t) {
            $t->dropConstrainedForeignId('student_id');
            $t->dropColumn('admitted_at');
        });
    }

    /** @param list<string> $tables */
    private function enableRls(array $tables): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $pred = "(
            COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
            OR school_id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
        )";

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("CREATE POLICY tenant_isolation ON {$table} USING {$pred} WITH CHECK {$pred}");
        }
    }
};
