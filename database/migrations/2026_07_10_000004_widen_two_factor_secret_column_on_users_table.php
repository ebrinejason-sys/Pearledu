<?php
// database/migrations/2026_07_10_000004_widen_two_factor_secret_column_on_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->text('two_factor_secret')->nullable()->change();
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->string('two_factor_secret')->nullable()->change();
        });
    }
};
