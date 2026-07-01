<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action');
            $t->string('entity_type')->nullable();
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->jsonb('metadata')->default('{}');
            $t->ipAddress('ip_address')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['school_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('audit_logs'); }
};
