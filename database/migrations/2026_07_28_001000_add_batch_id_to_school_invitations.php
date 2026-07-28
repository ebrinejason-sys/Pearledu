<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Batch id groups roles from one invite() call; acceptance activates only that batch. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_invitations', function (Blueprint $t) {
            $t->uuid('batch_id')->nullable()->after('invited_by')->index();
        });
    }

    public function down(): void
    {
        Schema::table('school_invitations', function (Blueprint $t) {
            $t->dropColumn('batch_id');
        });
    }
};
