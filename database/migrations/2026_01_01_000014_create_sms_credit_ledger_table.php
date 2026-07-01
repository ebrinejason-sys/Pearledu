<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Append-only credit ledger. Platform top-ups are positive deltas; sends are
// negative. A school's balance is the latest balance_after (or SUM(delta)).
return new class extends Migration {
    public function up(): void {
        Schema::create('sms_credit_ledger', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->integer('delta');                 // +credits (top-up) or -credits (spend)
            $t->integer('balance_after');         // running balance, written under row lock
            $t->string('reason');                 // topup|send|adjustment|refund
            $t->string('reference')->nullable();  // sms_message id / payment ref
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['school_id', 'id']);
        });
    }
    public function down(): void { Schema::dropIfExists('sms_credit_ledger'); }
};
