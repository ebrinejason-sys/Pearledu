<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }
            if (! Schema::hasColumn('students', 'religion')) {
                $table->string('religion', 80)->nullable()->after('nationality');
            }
            if (! Schema::hasColumn('students', 'home_address')) {
                $table->string('home_address', 255)->nullable()->after('religion');
            }
            if (! Schema::hasColumn('students', 'medical_notes')) {
                $table->text('medical_notes')->nullable()->after('home_address');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }
            if (! Schema::hasColumn('users', 'nationality')) {
                $table->string('nationality', 80)->nullable()->after('date_of_birth');
            }
            if (! Schema::hasColumn('users', 'home_address')) {
                $table->string('home_address', 255)->nullable()->after('nationality');
            }
        });

        Schema::create('staff_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('path');
            $table->string('original_name', 190)->nullable();
            $table->timestamps();
            $table->index(['school_id', 'user_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            $pred = "(
                COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
                OR school_id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
            )";
            DB::statement('ALTER TABLE staff_documents ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE staff_documents FORCE ROW LEVEL SECURITY');
            DB::statement('DROP POLICY IF EXISTS tenant_isolation ON staff_documents');
            DB::statement("CREATE POLICY tenant_isolation ON staff_documents USING {$pred} WITH CHECK {$pred}");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_documents');

        Schema::table('students', function (Blueprint $table) {
            foreach (['date_of_birth', 'religion', 'home_address', 'medical_notes'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['date_of_birth', 'nationality', 'home_address'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
