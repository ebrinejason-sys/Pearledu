<?php
// database/migrations/2026_07_10_000003_create_two_factor_email_codes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('two_factor_email_codes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('code_hash');
            $t->timestamp('expires_at');
            $t->timestamp('used_at')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamps();
            $t->index(['user_id', 'used_at', 'expires_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('two_factor_email_codes');
    }
};
