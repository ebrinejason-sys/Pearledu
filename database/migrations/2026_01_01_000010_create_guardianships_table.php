<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('guardianships', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('guardian_user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('relationship')->nullable();
            $t->boolean('is_primary')->default(false);
            $t->timestamps();
            $t->unique(['student_id', 'guardian_user_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('guardianships'); }
};
