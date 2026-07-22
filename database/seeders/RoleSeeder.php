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
            ['key' => 'school_admin', 'scope' => 'school', 'label' => 'School Admin'],
            ['key' => 'director', 'scope' => 'school', 'label' => 'Director'],
            ['key' => 'head_teacher', 'scope' => 'school', 'label' => 'Head Teacher'],
            ['key' => 'deputy_head_teacher', 'scope' => 'school', 'label' => 'Deputy Head Teacher'],
            ['key' => 'bursar', 'scope' => 'school', 'label' => 'Bursar'],
            ['key' => 'class_teacher', 'scope' => 'school', 'label' => 'Class Teacher'],
            ['key' => 'subject_teacher', 'scope' => 'school', 'label' => 'Subject Teacher'],
            ['key' => 'parent', 'scope' => 'school', 'label' => 'Parent'],
            ['key' => 'student', 'scope' => 'school', 'label' => 'Student'],
        ];
        foreach ($roles as $r) {
            Role::firstOrCreate(['key' => $r['key']], $r);
        }
    }
}
