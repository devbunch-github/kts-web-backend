<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedInteger('price_minor'); // e.g. 999 = $9.99
            $t->string('currency', 3)->default('USD');
            $t->json('features')->nullable();
            $t->string('stripe_plan_id')->nullable();
            $t->string('paypal_plan_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
