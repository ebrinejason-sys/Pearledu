<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_key_check');
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_key_check CHECK (key IN (
            'platform_admin','platform_ops','emis_data_entrant','support_agent',
            'school_admin','director','head_teacher','deputy_head_teacher',
            'bursar','class_teacher','subject_teacher','parent','student'
        ))");

        Schema::table('helpdesk_tickets', function (Blueprint $t) {
            $t->foreignId('assigned_to')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $t->string('priority', 20)->default('normal')->after('status');
            $t->string('category', 40)->nullable()->after('priority');
            $t->text('admin_notes')->nullable()->after('body');
            $t->timestamp('resolved_at')->nullable()->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_tickets', function (Blueprint $t) {
            $t->dropConstrainedForeignId('assigned_to');
            $t->dropColumn(['priority', 'category', 'admin_notes', 'resolved_at']);
        });

        DB::statement('ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_key_check');
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_key_check CHECK (key IN (
            'platform_admin','school_admin','director','head_teacher','deputy_head_teacher',
            'bursar','class_teacher','subject_teacher','parent','student'
        ))");
    }
};
