<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Platform-level SMS configuration (single row). NOT tenant-scoped — only the
// platform admin controls the provider, sender id and per-segment credit cost.
return new class extends Migration {
    public function up(): void {
        Schema::create('sms_settings', function (Blueprint $t) {
            $t->id();
            $t->string('provider')->default('fake');         // fake|africastalking|...
            $t->string('sender_id')->nullable();
            $t->unsignedInteger('segment_credits')->default(1); // credits charged per 160-char segment
            $t->boolean('is_enabled')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sms_settings'); }
};
