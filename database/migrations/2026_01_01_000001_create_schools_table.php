<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('schools', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug');
            $t->string('emis_number')->nullable();
            $t->string('district')->nullable();
            $t->string('theme')->default('pearledu');       // pearledu|moodle|emis
            $t->string('status')->default('pending');       // pending|active|suspended|archived
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        DB::statement("CREATE UNIQUE INDEX schools_slug_ci_unique ON schools (lower(slug))");
        DB::statement("CREATE UNIQUE INDEX schools_emis_unique ON schools (emis_number) WHERE emis_number IS NOT NULL");
        DB::statement("ALTER TABLE schools ADD CONSTRAINT schools_status_check CHECK (status IN ('pending','active','suspended','archived'))");
        DB::statement("ALTER TABLE schools ADD CONSTRAINT schools_slug_format_check CHECK (slug ~ '^[a-z0-9](?:[a-z0-9-]{1,38}[a-z0-9])$')");
    }
    public function down(): void { Schema::dropIfExists('schools'); }
};
