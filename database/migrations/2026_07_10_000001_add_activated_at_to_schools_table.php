<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('schools', function (Blueprint $t) {
            $t->timestamp('activated_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('schools', function (Blueprint $t) {
            $t->dropColumn('activated_at');
        });
    }
};
