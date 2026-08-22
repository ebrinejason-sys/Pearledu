<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['key' => 'platform_admin', 'scope' => 'platform', 'label' => 'Platform Admin'],
            ['key' => 'platform_ops', 'scope' => 'platform', 'label' => 'Platform Operations'],
            ['key' => 'emis_data_entrant', 'scope' => 'platform', 'label' => 'EMIS Data Entrant'],
            ['key' => 'support_agent', 'scope' => 'platform', 'label' => 'Support Agent'],
            ['key' => 'school_admin', 'scope' => 'school', 'label' => 'School Admin'],
            ['key' => 'director', 'scope' => 'school', 'label' => 'Director'],
            ['key' => 'head_teacher', 'scope' => 'school', 'label' => 'Head Teacher'],
            ['key' => 'deputy_head_teacher', 'scope' => 'school', 'label' => 'Deputy Head Teacher'],
            ['key' => 'director_of_studies', 'scope' => 'school', 'label' => 'Director of Studies'],
            ['key' => 'bursar', 'scope' => 'school', 'label' => 'Bursar'],
            ['key' => 'secretary', 'scope' => 'school', 'label' => 'Secretary'],
            ['key' => 'class_teacher', 'scope' => 'school', 'label' => 'Class Teacher'],
            ['key' => 'subject_teacher', 'scope' => 'school', 'label' => 'Teacher'],
            ['key' => 'parent', 'scope' => 'school', 'label' => 'Parent'],
            ['key' => 'student', 'scope' => 'school', 'label' => 'Student'],
        ];
        foreach ($roles as $r) {
            Role::updateOrCreate(['key' => $r['key']], $r);
        }
    }
}
