<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent catch-up: staged school deletion columns + status check.
 * Safe if 2026_07_25_000700 already ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $t) {
            if (! Schema::hasColumn('schools', 'deletion_scheduled_at')) {
                $t->timestamp('deletion_scheduled_at')->nullable();
            }
            if (! Schema::hasColumn('schools', 'deletion_requested_by')) {
                $t->foreignId('deletion_requested_by')->nullable()
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('schools', 'deletion_reason')) {
                $t->string('deletion_reason', 500)->nullable();
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE schools DROP CONSTRAINT IF EXISTS schools_status_check');
            DB::statement("ALTER TABLE schools ADD CONSTRAINT schools_status_check CHECK (status IN ('pending','active','suspended','archived','deletion_scheduled'))");
        }
    }

    public function down(): void
    {
        // Keep columns: down would collide with 000700 and drop live deletion state.
    }
};
