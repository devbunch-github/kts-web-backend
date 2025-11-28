<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gift_card_usages', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('gift_card_purchase_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('account_id');

            $table->decimal('used_amount', 10, 2);
            $table->timestamps();

            $table->index(['gift_card_purchase_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_usages');
    }
};
