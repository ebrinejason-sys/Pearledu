<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_key_check');
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_key_check CHECK (key IN (
            'platform_admin','platform_ops','emis_data_entrant','support_agent',
            'school_admin','director','head_teacher','deputy_head_teacher','director_of_studies',
            'bursar','secretary','class_teacher','subject_teacher','parent','student'
        ))");

        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 16)->nullable()->after('phone');
            $table->text('nin')->nullable()->after('gender');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('gender', 16)->nullable()->after('full_name');
            $table->string('photo_path', 255)->nullable()->after('nin');
        });

        Schema::create('staff_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'user_id']);
            $table->unique(['school_id', 'code']);
        });

        Schema::create('staff_time_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('direction', 8);
            $table->string('source', 16)->default('scan');
            $table->timestamp('punched_at');
            $table->timestamps();
            $table->index(['school_id', 'user_id', 'punched_at']);
        });

        Schema::create('staff_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 160)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('staff_conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('staff_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('staff_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('staff_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('staff_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 8)->default('UGX');
            $table->date('effective_on');
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'user_id']);
        });

        Schema::create('staff_salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 8)->default('UGX');
            $table->date('paid_on');
            $table->string('method', 32)->default('bank');
            $table->string('reference', 80)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->index(['school_id', 'user_id', 'paid_on']);
        });

        if (DB::getDriverName() === 'pgsql') {
            $pred = "(
                COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
                OR school_id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
            )";
            foreach ([
                'staff_badges',
                'staff_time_punches',
                'staff_conversations',
                'staff_conversation_participants',
                'staff_messages',
                'staff_salaries',
                'staff_salary_payments',
            ] as $table) {
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
                DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
                DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
                DB::statement("CREATE POLICY tenant_isolation ON {$table} USING {$pred} WITH CHECK {$pred}");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_salary_payments');
        Schema::dropIfExists('staff_salaries');
        Schema::dropIfExists('staff_messages');
        Schema::dropIfExists('staff_conversation_participants');
        Schema::dropIfExists('staff_conversations');
        Schema::dropIfExists('staff_time_punches');
        Schema::dropIfExists('staff_badges');

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['gender', 'photo_path']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gender', 'nin']);
        });

        DB::statement('ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_key_check');
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_key_check CHECK (key IN (
            'platform_admin','platform_ops','emis_data_entrant','support_agent',
            'school_admin','director','head_teacher','deputy_head_teacher','director_of_studies',
            'bursar','class_teacher','subject_teacher','parent','student'
        ))");
    }
};
