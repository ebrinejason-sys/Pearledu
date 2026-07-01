<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('teaching_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assignment_id')->constrained('role_assignments')->cascadeOnDelete();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $t->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['assignment_id', 'subject_id', 'class_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('teaching_assignments'); }
};
