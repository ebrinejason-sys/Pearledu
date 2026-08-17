<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_marksheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_period_id')->constrained('assessment_periods')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('status', 20)->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['school_id', 'assessment_period_id', 'class_id', 'subject_id'],
                'assessment_marksheets_unique'
            );
        });

        DB::table('roles')->where('key', 'subject_teacher')->update(['label' => 'Teacher']);

        if (DB::getDriverName() === 'pgsql') {
            $pred = "(
                COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
                OR school_id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
            )";
            DB::statement('ALTER TABLE assessment_marksheets ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE assessment_marksheets FORCE ROW LEVEL SECURITY');
            DB::statement('DROP POLICY IF EXISTS tenant_isolation ON assessment_marksheets');
            DB::statement("CREATE POLICY tenant_isolation ON assessment_marksheets USING {$pred} WITH CHECK {$pred}");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_marksheets');
        DB::table('roles')->where('key', 'subject_teacher')->update(['label' => 'Subject Teacher']);
    }
};
