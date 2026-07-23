<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbt_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('exam_id')->constrained('cbt_exams')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->decimal('score', 8, 2)->default(0);
            $t->decimal('max_score', 8, 2)->default(0);
            $t->timestamp('started_at')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->string('status', 20)->default('in_progress'); // in_progress|submitted
            $t->timestamps();
            $t->unique(['exam_id', 'student_id']);
        });

        Schema::create('cbt_attempt_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('attempt_id')->constrained('cbt_attempts')->cascadeOnDelete();
            $t->foreignId('question_id')->constrained('cbt_questions')->cascadeOnDelete();
            $t->string('chosen_key', 1)->nullable();
            $t->boolean('is_correct')->default(false);
            $t->decimal('points_awarded', 8, 2)->default(0);
            $t->timestamps();
            $t->unique(['attempt_id', 'question_id']);
        });

        Schema::create('lms_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('assignment_id')->constrained('lms_assignments')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('body')->nullable();
            $t->string('url', 500)->nullable();
            $t->decimal('score', 8, 2)->nullable();
            $t->text('feedback')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('graded_at')->nullable();
            $t->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['assignment_id', 'student_id']);
        });

        $pred = "(
            COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
            OR school_id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
        )";

        foreach (['cbt_attempts', 'cbt_attempt_answers', 'lms_submissions'] as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY tenant_isolation ON {$table} USING {$pred} WITH CHECK {$pred}");
        }
    }

    public function down(): void
    {
        foreach (['lms_submissions', 'cbt_attempt_answers', 'cbt_attempts'] as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            Schema::dropIfExists($table);
        }
    }
};
