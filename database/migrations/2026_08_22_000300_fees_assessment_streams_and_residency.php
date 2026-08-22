<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('residency', 16)->default('day')->after('gender');
            $table->string('nationality', 80)->nullable()->after('residency');
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->string('kind', 24)->default('tuition')->after('name');
            $table->string('residency', 16)->default('any')->after('kind');
            $table->string('applies_to', 16)->default('class')->after('residency');
        });

        Schema::create('fee_structure_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['fee_structure_id', 'student_id']);
        });

        Schema::table('assessment_periods', function (Blueprint $table) {
            $table->string('kind', 16)->default('custom')->after('name');
            $table->date('entry_deadline')->nullable()->after('max_score');
        });

        Schema::table('assessment_marksheets', function (Blueprint $table) {
            $table->timestamp('upload_revoked_at')->nullable()->after('verified_at');
            $table->foreignId('upload_revoked_by')->nullable()->after('upload_revoked_at')->constrained('users')->nullOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            $pred = "(
                COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
                OR school_id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
            )";
            DB::statement('ALTER TABLE fee_structure_students ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE fee_structure_students FORCE ROW LEVEL SECURITY');
            DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fee_structure_students');
            DB::statement("CREATE POLICY tenant_isolation ON fee_structure_students USING {$pred} WITH CHECK {$pred}");

            DB::statement("
                DO \$\$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = 'fee_structures_school_id_id_unique'
                    ) THEN
                        ALTER TABLE fee_structures ADD CONSTRAINT fee_structures_school_id_id_unique UNIQUE (school_id, id);
                    END IF;
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = 'students_school_id_id_unique'
                    ) THEN
                        ALTER TABLE students ADD CONSTRAINT students_school_id_id_unique UNIQUE (school_id, id);
                    END IF;
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = 'fee_structure_students_school_fee_structure_id_fk'
                    ) THEN
                        ALTER TABLE fee_structure_students
                            ADD CONSTRAINT fee_structure_students_school_fee_structure_id_fk
                            FOREIGN KEY (school_id, fee_structure_id)
                            REFERENCES fee_structures (school_id, id)
                            ON DELETE CASCADE;
                    END IF;
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = 'fee_structure_students_school_student_id_fk'
                    ) THEN
                        ALTER TABLE fee_structure_students
                            ADD CONSTRAINT fee_structure_students_school_student_id_fk
                            FOREIGN KEY (school_id, student_id)
                            REFERENCES students (school_id, id)
                            ON DELETE CASCADE;
                    END IF;
                END
                \$\$;
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fee_structure_students');
            DB::statement('ALTER TABLE fee_structure_students DROP CONSTRAINT IF EXISTS fee_structure_students_school_fee_structure_id_fk');
            DB::statement('ALTER TABLE fee_structure_students DROP CONSTRAINT IF EXISTS fee_structure_students_school_student_id_fk');
        }

        Schema::table('assessment_marksheets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('upload_revoked_by');
            $table->dropColumn('upload_revoked_at');
        });
        Schema::table('assessment_periods', function (Blueprint $table) {
            $table->dropColumn(['kind', 'entry_deadline']);
        });
        Schema::dropIfExists('fee_structure_students');
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn(['kind', 'residency', 'applies_to']);
        });
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['residency', 'nationality']);
        });
    }
};
