<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parent portal payments must not clear invoice balances until staff confirms.
 * Also harden admission_applications.requested_class_id with a composite tenant FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_payments', function (Blueprint $t) {
            $t->string('status')->default('confirmed')->after('provider_ref'); // pending|confirmed|rejected
            $t->foreignId('verified_by')->nullable()->after('recorded_by')->constrained('users')->nullOnDelete();
            $t->timestamp('verified_at')->nullable()->after('verified_by');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Ensure parent unique for composite FK (idempotent with 001200).
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'school_classes_school_id_id_unique'
                ) THEN
                    ALTER TABLE school_classes
                        ADD CONSTRAINT school_classes_school_id_id_unique UNIQUE (school_id, id);
                END IF;
            END
            \$\$;
        ");

        DB::statement('ALTER TABLE admission_applications DROP CONSTRAINT IF EXISTS admission_applications_requested_class_id_foreign');

        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'admission_applications_school_requested_class_id_fk'
                ) THEN
                    ALTER TABLE admission_applications
                        ADD CONSTRAINT admission_applications_school_requested_class_id_fk
                        FOREIGN KEY (school_id, requested_class_id)
                        REFERENCES school_classes (school_id, id)
                        ON DELETE SET NULL;
                END IF;
            END
            \$\$;
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE admission_applications DROP CONSTRAINT IF EXISTS admission_applications_school_requested_class_id_fk');
            DB::statement("
                DO \$\$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = 'admission_applications_requested_class_id_foreign'
                    ) THEN
                        ALTER TABLE admission_applications
                            ADD CONSTRAINT admission_applications_requested_class_id_foreign
                            FOREIGN KEY (requested_class_id)
                            REFERENCES school_classes (id)
                            ON DELETE SET NULL;
                    END IF;
                END
                \$\$;
            ");
        }

        Schema::table('fee_payments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('verified_by');
            $t->dropColumn(['status', 'verified_at']);
        });
    }
};
