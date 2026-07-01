<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('roles', function (Blueprint $t) {
            $t->smallIncrements('id');
            $t->string('key')->unique();
            $t->string('scope');
            $t->string('label');
        });
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_key_check CHECK (key IN ('platform_admin','school_admin','director','head_teacher','bursar','class_teacher','subject_teacher','parent','student'))");
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_scope_check CHECK (scope IN ('platform','school'))");
    }
    public function down(): void { Schema::dropIfExists('roles'); }
};
