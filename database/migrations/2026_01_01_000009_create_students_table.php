<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('students', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('full_name');
            $t->string('emis_number')->nullable();
            $t->text('lin')->nullable();   // encrypted at app layer
            $t->text('nin')->nullable();   // encrypted at app layer
            $t->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->string('status')->default('active');
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['school_id', 'emis_number']);
        });
        DB::statement("ALTER TABLE students ADD CONSTRAINT students_status_check CHECK (status IN ('active','inactive','transferred','graduated'))");
    }
    public function down(): void { Schema::dropIfExists('students'); }
};
