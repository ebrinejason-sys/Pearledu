<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('name'); $t->string('code');
            $t->timestamps();
            $t->unique(['school_id', 'code']);
        });
    }
    public function down(): void { Schema::dropIfExists('subjects'); }
};
