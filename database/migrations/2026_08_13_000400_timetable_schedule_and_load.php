<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable_periods', function (Blueprint $t) {
            if (! Schema::hasColumn('timetable_periods', 'kind')) {
                $t->string('kind', 32)->default('class')->after('name');
            }
        });

        Schema::table('teaching_assignments', function (Blueprint $t) {
            if (! Schema::hasColumn('teaching_assignments', 'periods_per_week')) {
                $t->unsignedTinyInteger('periods_per_week')->default(3)->after('status');
            }
        });

        Schema::table('schools', function (Blueprint $t) {
            if (! Schema::hasColumn('schools', 'schedule_settings')) {
                $t->json('schedule_settings')->nullable()->after('report_settings');
            }
        });
    }

    public function down(): void
    {
        Schema::table('timetable_periods', function (Blueprint $t) {
            if (Schema::hasColumn('timetable_periods', 'kind')) {
                $t->dropColumn('kind');
            }
        });
        Schema::table('teaching_assignments', function (Blueprint $t) {
            if (Schema::hasColumn('teaching_assignments', 'periods_per_week')) {
                $t->dropColumn('periods_per_week');
            }
        });
        Schema::table('schools', function (Blueprint $t) {
            if (Schema::hasColumn('schools', 'schedule_settings')) {
                $t->dropColumn('schedule_settings');
            }
        });
    }
};
