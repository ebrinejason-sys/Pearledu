<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('school_domains', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('domain');
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
        });
        DB::statement("CREATE UNIQUE INDEX school_domains_domain_ci_unique ON school_domains (lower(domain))");
    }
    public function down(): void { Schema::dropIfExists('school_domains'); }
};
