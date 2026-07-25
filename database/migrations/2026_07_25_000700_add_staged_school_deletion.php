<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staged tenant deletion: schedule → retention → purge.
 * Do not run in this wave if ops prefer to migrate later — file is ready.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $t) {
            $t->timestamp('deletion_scheduled_at')->nullable()->after('activated_at');
            $t->foreignId('deletion_requested_by')->nullable()->after('deletion_scheduled_at')
                ->constrained('users')->nullOnDelete();
            $t->string('deletion_reason', 500)->nullable()->after('deletion_requested_by');
        });

        DB::statement('ALTER TABLE schools DROP CONSTRAINT IF EXISTS schools_status_check');
        DB::statement("ALTER TABLE schools ADD CONSTRAINT schools_status_check CHECK (status IN ('pending','active','suspended','archived','deletion_scheduled'))");
    }

    public function down(): void
    {
        DB::table('schools')->where('status', 'deletion_scheduled')->update([
            'status' => 'archived',
            'deletion_scheduled_at' => null,
            'deletion_requested_by' => null,
            'deletion_reason' => null,
        ]);

        DB::statement('ALTER TABLE schools DROP CONSTRAINT IF EXISTS schools_status_check');
        DB::statement("ALTER TABLE schools ADD CONSTRAINT schools_status_check CHECK (status IN ('pending','active','suspended','archived'))");

        Schema::table('schools', function (Blueprint $t) {
            $t->dropConstrainedForeignId('deletion_requested_by');
            $t->dropColumn(['deletion_scheduled_at', 'deletion_reason']);
        });
    }
};
