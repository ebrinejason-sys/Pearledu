<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Composite tenant FKs: child (school_id, parent_id) must reference a row in the
 * same school. Prevents mixed-tenant references even under platform scope.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $parents = [
        'students',
        'school_classes',
        'subjects',
        'assessment_periods',
        'academic_years',
        'fee_structures',
        'fee_invoices',
        'lms_assignments',
        'cbt_exams',
        'rooms',
        'timetable_periods',
    ];

    /**
     * child => list of [column, parentTable]
     *
     * @var array<string, list<array{0:string,1:string}>>
     */
    private array $composites = [
        'marks' => [
            ['student_id', 'students'],
            ['class_id', 'school_classes'],
            ['subject_id', 'subjects'],
            ['assessment_period_id', 'assessment_periods'],
        ],
        'attendance_records' => [
            ['student_id', 'students'],
            ['class_id', 'school_classes'],
        ],
        'enrollments' => [
            ['student_id', 'students'],
            ['class_id', 'school_classes'],
            ['academic_year_id', 'academic_years'],
        ],
        'guardianships' => [
            ['student_id', 'students'],
        ],
        'teaching_assignments' => [
            ['class_id', 'school_classes'],
            ['subject_id', 'subjects'],
            ['academic_year_id', 'academic_years'],
        ],
        'fee_invoices' => [
            ['student_id', 'students'],
            ['fee_structure_id', 'fee_structures'],
        ],
        'fee_payments' => [
            ['invoice_id', 'fee_invoices'],
        ],
        'lms_submissions' => [
            ['student_id', 'students'],
            ['assignment_id', 'lms_assignments'],
        ],
        'cbt_attempts' => [
            ['student_id', 'students'],
            ['exam_id', 'cbt_exams'],
        ],
        'timetable_slots' => [
            ['class_id', 'school_classes'],
            ['subject_id', 'subjects'],
            ['period_id', 'timetable_periods'],
            ['room_id', 'rooms'],
        ],
        'students' => [
            ['class_id', 'school_classes'],
        ],
        'announcements' => [
            ['class_id', 'school_classes'],
        ],
        'lms_materials' => [
            ['class_id', 'school_classes'],
            ['subject_id', 'subjects'],
        ],
        'lms_assignments' => [
            ['class_id', 'school_classes'],
            ['subject_id', 'subjects'],
        ],
        'cbt_exams' => [
            ['class_id', 'school_classes'],
            ['subject_id', 'subjects'],
        ],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->parents as $parent) {
            if (! Schema::hasTable($parent)) {
                continue;
            }
            $constraint = "{$parent}_school_id_id_unique";
            DB::statement("
                DO \$\$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = '{$constraint}'
                    ) THEN
                        ALTER TABLE {$parent}
                            ADD CONSTRAINT {$constraint} UNIQUE (school_id, id);
                    END IF;
                END
                \$\$;
            ");
        }

        foreach ($this->composites as $child => $refs) {
            if (! Schema::hasTable($child)) {
                continue;
            }

            foreach ($refs as [$column, $parent]) {
                if (! Schema::hasColumn($child, $column) || ! Schema::hasTable($parent)) {
                    continue;
                }

                // Drop simple FK if present (Laravel default name).
                $simple = "{$child}_{$column}_foreign";
                DB::statement("ALTER TABLE {$child} DROP CONSTRAINT IF EXISTS {$simple}");

                $composite = "{$child}_school_{$column}_fk";
                $nullability = DB::selectOne(
                    'SELECT is_nullable FROM information_schema.columns
                     WHERE table_schema = current_schema()
                       AND table_name = ?
                       AND column_name = ?',
                    [$child, $column]
                );
                $onDelete = ($nullability && $nullability->is_nullable === 'YES') ? 'SET NULL' : 'CASCADE';

                DB::statement("
                    DO \$\$
                    BEGIN
                        IF NOT EXISTS (
                            SELECT 1 FROM pg_constraint WHERE conname = '{$composite}'
                        ) THEN
                            ALTER TABLE {$child}
                                ADD CONSTRAINT {$composite}
                                FOREIGN KEY (school_id, {$column})
                                REFERENCES {$parent} (school_id, id)
                                ON DELETE {$onDelete};
                        END IF;
                    END
                    \$\$;
                ");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->composites as $child => $refs) {
            if (! Schema::hasTable($child)) {
                continue;
            }
            foreach ($refs as [$column, $parent]) {
                $composite = "{$child}_school_{$column}_fk";
                DB::statement("ALTER TABLE {$child} DROP CONSTRAINT IF EXISTS {$composite}");

                // Best-effort restore of simple FK (nullable columns use nullOnDelete where typical).
                if (! Schema::hasColumn($child, $column) || ! Schema::hasTable($parent)) {
                    continue;
                }
                $simple = "{$child}_{$column}_foreign";
                $onDelete = in_array($column, ['class_id', 'subject_id', 'room_id', 'period_id', 'fee_structure_id'], true)
                    ? 'SET NULL'
                    : 'CASCADE';
                // Only re-add if column is still without an FK.
                DB::statement("
                    DO \$\$
                    BEGIN
                        IF NOT EXISTS (
                            SELECT 1 FROM pg_constraint WHERE conname = '{$simple}'
                        ) AND NOT EXISTS (
                            SELECT 1 FROM pg_constraint WHERE conname = '{$composite}'
                        ) THEN
                            ALTER TABLE {$child}
                                ADD CONSTRAINT {$simple}
                                FOREIGN KEY ({$column})
                                REFERENCES {$parent} (id)
                                ON DELETE {$onDelete};
                        END IF;
                    END
                    \$\$;
                ");
            }
        }

        foreach ($this->parents as $parent) {
            if (! Schema::hasTable($parent)) {
                continue;
            }
            DB::statement("ALTER TABLE {$parent} DROP CONSTRAINT IF EXISTS {$parent}_school_id_id_unique");
        }
    }
};
