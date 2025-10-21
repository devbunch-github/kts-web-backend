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
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('AccountId')->nullable();

            // PayPal fields
            $t->boolean('paypal_active')->default(false);
            $t->text('paypal_client_id')->nullable();
            $t->text('paypal_client_secret')->nullable();
            $t->string('paypal_email')->nullable();

            // Stripe fields
            $t->boolean('stripe_active')->default(false);
            $t->text('stripe_public_key')->nullable();
            $t->text('stripe_secret_key')->nullable();

            // Pay at venue
            $t->boolean('pay_at_venue')->default(false);

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
