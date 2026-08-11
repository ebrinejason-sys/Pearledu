<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SchoolPay (schoolpay.co.ug) integration:
 * - Per-school credentials (code + encrypted API password)
 * - Student payment codes for traditional SchoolPay channel payments
 * - Fee payment provider columns for adhoc refs + receipt idempotency
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $t) {
            $t->boolean('schoolpay_enabled')->default(false)->after('badge_text');
            $t->string('schoolpay_school_code', 32)->nullable()->after('schoolpay_enabled');
            $t->text('schoolpay_api_password')->nullable()->after('schoolpay_school_code');
        });

        Schema::table('students', function (Blueprint $t) {
            $t->string('schoolpay_payment_code', 32)->nullable()->after('emis_number');
        });

        DB::statement('CREATE UNIQUE INDEX students_school_schoolpay_code_unique ON students (school_id, schoolpay_payment_code) WHERE schoolpay_payment_code IS NOT NULL AND deleted_at IS NULL');

        Schema::table('fee_payments', function (Blueprint $t) {
            $t->string('external_reference', 64)->nullable()->after('provider_ref');
            $t->string('schoolpay_reference', 64)->nullable()->after('external_reference');
            $t->string('provider_txn_id', 64)->nullable()->after('schoolpay_reference');
            $t->string('channel_name', 80)->nullable()->after('provider_txn_id');
        });

        DB::statement('CREATE UNIQUE INDEX fee_payments_school_external_ref_unique ON fee_payments (school_id, external_reference) WHERE external_reference IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX fee_payments_school_provider_txn_unique ON fee_payments (school_id, provider_txn_id) WHERE provider_txn_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX fee_payments_school_schoolpay_ref_unique ON fee_payments (school_id, schoolpay_reference) WHERE schoolpay_reference IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS fee_payments_school_schoolpay_ref_unique');
        DB::statement('DROP INDEX IF EXISTS fee_payments_school_provider_txn_unique');
        DB::statement('DROP INDEX IF EXISTS fee_payments_school_external_ref_unique');
        DB::statement('DROP INDEX IF EXISTS students_school_schoolpay_code_unique');

        Schema::table('fee_payments', function (Blueprint $t) {
            $t->dropColumn(['external_reference', 'schoolpay_reference', 'provider_txn_id', 'channel_name']);
        });

        Schema::table('students', function (Blueprint $t) {
            $t->dropColumn('schoolpay_payment_code');
        });

        Schema::table('schools', function (Blueprint $t) {
            $t->dropColumn(['schoolpay_enabled', 'schoolpay_school_code', 'schoolpay_api_password']);
        });
    }
};
