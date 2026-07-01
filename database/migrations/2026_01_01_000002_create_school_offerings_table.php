<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('school_offerings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('level');
            $t->timestamps();
            $t->unique(['school_id', 'level']);
        });
        DB::statement("ALTER TABLE school_offerings ADD CONSTRAINT offerings_level_check CHECK (level IN ('preprimary','primary','lower_secondary','upper_secondary'))");
    }
    public function down(): void { Schema::dropIfExists('school_offerings'); }
};
