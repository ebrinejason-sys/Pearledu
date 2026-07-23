<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $t) {
            $t->string('motto', 200)->nullable()->after('theme');
            $t->string('badge_text', 80)->nullable()->after('motto');
            $t->string('logo_path', 255)->nullable()->after('badge_text');
            $t->string('address', 255)->nullable()->after('logo_path');
        });

        Schema::table('fee_structures', function (Blueprint $t) {
            $t->boolean('is_active')->default(true)->after('currency');
        });

        Schema::create('transport_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('route_id')->constrained('transport_routes')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->timestamps();
            $t->unique(['route_id', 'student_id', 'starts_on']);
        });

        $pred = "(
            COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
            OR school_id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
        )";
        DB::statement('ALTER TABLE transport_allocations ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE transport_allocations FORCE ROW LEVEL SECURITY');
        DB::statement("CREATE POLICY tenant_isolation ON transport_allocations USING {$pred} WITH CHECK {$pred}");
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON transport_allocations');
        Schema::dropIfExists('transport_allocations');
        Schema::table('fee_structures', function (Blueprint $t) {
            $t->dropColumn('is_active');
        });
        Schema::table('schools', function (Blueprint $t) {
            $t->dropColumn(['motto', 'badge_text', 'logo_path', 'address']);
        });
    }
};
