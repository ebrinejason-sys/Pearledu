<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('school_invitations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('role_key');
            $t->string('token_hash');
            $t->timestamp('expires_at');
            $t->timestamp('accepted_at')->nullable();
            $t->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('school_invitations'); }
};
