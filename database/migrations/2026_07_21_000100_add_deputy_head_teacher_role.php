<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_key_check');
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_key_check CHECK (key IN (
            'platform_admin','school_admin','director','head_teacher','deputy_head_teacher',
            'bursar','class_teacher','subject_teacher','parent','student'
        ))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_key_check');
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_key_check CHECK (key IN (
            'platform_admin','school_admin','director','head_teacher',
            'bursar','class_teacher','subject_teacher','parent','student'
        ))");
    }
};
