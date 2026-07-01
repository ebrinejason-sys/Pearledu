<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('full_name');
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('password')->nullable();
            $t->string('status')->default('invited');      // invited|active|disabled
            $t->boolean('is_platform')->default(false);
            $t->string('two_factor_secret')->nullable();
            $t->timestamp('two_factor_confirmed_at')->nullable();
            $t->string('preferred_theme')->nullable();      // overrides school theme if set
            $t->timestamp('email_verified_at')->nullable();
            $t->timestamp('last_login_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
            $t->softDeletes();                               // supports account-deletion grace + audit
        });
        DB::statement("CREATE UNIQUE INDEX users_email_ci_unique ON users (lower(email)) WHERE email IS NOT NULL AND deleted_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX users_phone_unique ON users (phone) WHERE phone IS NOT NULL AND deleted_at IS NULL");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('invited','active','disabled'))");

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
    }
    public function down(): void {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
