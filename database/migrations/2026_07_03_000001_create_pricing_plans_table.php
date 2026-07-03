<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PearlEdu landing-page pricing tiers. Platform-level (like sms_settings):
// NOT tenant-scoped, no RLS — only platform operators manage them, and the
// public landing page reads them anonymously.
return new class extends Migration {
    public function up(): void {
        Schema::create('pricing_plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('tagline')->nullable();
            $t->unsignedBigInteger('price')->nullable();   // null => "Contact us"
            $t->string('currency', 8)->default('UGX');
            $t->string('billing_period')->default('per term');
            $t->json('features')->default('[]');           // list of feature strings
            $t->boolean('is_highlighted')->default(false);
            $t->boolean('is_active')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('pricing_plans'); }
};
