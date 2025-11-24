<?php

// database/migrations/2025_11_24_000000_create_gift_card_purchases_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('GiftCardPurchases', function (Blueprint $table) {
            $table->bigIncrements('Id');

            // Foreigns (follow your existing naming style)
            $table->unsignedBigInteger('GiftCardId');
            $table->unsignedBigInteger('CustomerId');
            $table->unsignedBigInteger('AccountId');

            // Business fields
            $table->string('Code')->unique();              // actual redeemable code
            $table->decimal('Amount', 10, 2);

            $table->string('PaymentMethod')->nullable();   // stripe, paypal
            $table->string('PaymentStatus')->default('pending'); // pending, paid, failed

            $table->string('StripeSessionId')->nullable();
            $table->string('PayPalOrderId')->nullable();

            $table->timestamp('ExpiresAt')->nullable();    // e.g. +12 months
            $table->timestamp('PaidAt')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('GiftCardPurchases');
    }
};
