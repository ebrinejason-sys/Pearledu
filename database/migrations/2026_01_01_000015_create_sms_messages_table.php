<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('sms_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('to_phone');
            $t->text('body');
            $t->unsignedInteger('segments')->default(1);
            $t->unsignedInteger('cost_credits')->default(0);
            $t->string('category')->default('general'); // auth|fees|results|attendance|general
            $t->string('status')->default('queued');    // queued|sent|failed
            $t->string('provider_ref')->nullable();
            $t->text('error')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['school_id', 'status']);
        });
        DB::statement("ALTER TABLE sms_messages ADD CONSTRAINT sms_status_check CHECK (status IN ('queued','sent','failed'))");
    }
    public function down(): void { Schema::dropIfExists('sms_messages'); }
};
