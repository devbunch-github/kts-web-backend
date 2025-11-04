<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('loyalty_program_settings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('account_id')->index();
            $t->boolean('is_enabled')->default(false);
            $t->unsignedInteger('points_per_currency')->default(1); // e.g., 1 point per £1
            $t->unsignedInteger('points_per_redemption_currency')->default(50); // points needed per £1 off
            $t->timestamps();
        });

        Schema::create('loyalty_program_service', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('loyalty_program_setting_id')->index();
            $t->unsignedBigInteger('service_id')->index();
            $t->timestamps();
        });

        Schema::create('loyalty_program_ledgers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('account_id')->index();
            $t->unsignedBigInteger('customer_id')->index();
            $t->integer('points_balance')->default(0);
            $t->timestamps();
        });

        Schema::create('loyalty_program_transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('account_id')->index();
            $t->unsignedBigInteger('customer_id')->index();
            $t->enum('type', ['earn','redeem']);
            $t->integer('points'); // +/-
            $t->decimal('currency_value', 10, 2)->default(0); // optional: £ value earned/redeemed
            $t->string('reference_type')->nullable(); // e.g., 'order'
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('loyalty_program_transactions');
        Schema::dropIfExists('loyalty_program_ledgers');
        Schema::dropIfExists('loyalty_program_service');
        Schema::dropIfExists('loyalty_program_settings');
    }
};
