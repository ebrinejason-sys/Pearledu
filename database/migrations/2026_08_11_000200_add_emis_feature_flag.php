<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Optional school features: EMIS Support can be opted in/out like SchoolPay. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $t) {
            $t->boolean('emis_enabled')->default(false)->after('schoolpay_api_password');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $t) {
            $t->dropColumn('emis_enabled');
        });
    }
};
