<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_key_check');
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_key_check CHECK (key IN (
            'platform_admin','platform_ops','emis_data_entrant','support_agent',
            'school_admin','director','head_teacher','deputy_head_teacher','director_of_studies',
            'bursar','class_teacher','subject_teacher','parent','student'
        ))");

        DB::table('roles')->insertOrIgnore([
            'key' => 'director_of_studies',
            'scope' => 'school',
            'label' => 'Director of Studies',
        ]);
    }

    public function down(): void
    {
        DB::table('role_assignments')->whereIn('role_id', function ($query) {
            $query->select('id')->from('roles')->where('key', 'director_of_studies');
        })->delete();
        DB::table('roles')->where('key', 'director_of_studies')->delete();

        DB::statement('ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_key_check');
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_key_check CHECK (key IN (
            'platform_admin','platform_ops','emis_data_entrant','support_agent',
            'school_admin','director','head_teacher','deputy_head_teacher',
            'bursar','class_teacher','subject_teacher','parent','student'
        ))");
    }
};
