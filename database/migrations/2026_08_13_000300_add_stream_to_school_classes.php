<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $t) {
            if (! Schema::hasColumn('school_classes', 'stream')) {
                $t->string('stream', 40)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $t) {
            if (Schema::hasColumn('school_classes', 'stream')) {
                $t->dropColumn('stream');
            }
        });
    }
};
