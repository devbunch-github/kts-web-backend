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
        Schema::create('payment_settings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable(); // super admin id
            $t->string('paypal_client_id')->nullable();
            $t->string('paypal_client_secret')->nullable();
            $t->string('paypal_email')->nullable();
            $t->string('stripe_public_key')->nullable();
            $t->string('stripe_secret_key')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};