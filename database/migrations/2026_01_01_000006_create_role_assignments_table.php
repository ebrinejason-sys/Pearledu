<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('role_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedSmallInteger('role_id');
            $t->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $t->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->boolean('is_active')->default(true);
            $t->date('starts_on')->default(DB::raw('CURRENT_DATE'));
            $t->date('ends_on')->nullable();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->foreign('role_id')->references('id')->on('roles');
        });
        DB::statement("CREATE UNIQUE INDEX uniq_active_assignment ON role_assignments (user_id, role_id, COALESCE(school_id,0), COALESCE(class_id,0)) WHERE is_active");
    }
    public function down(): void { Schema::dropIfExists('role_assignments'); }
};
