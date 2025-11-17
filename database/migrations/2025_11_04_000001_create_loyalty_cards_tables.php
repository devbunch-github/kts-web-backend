<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('loyalty_card_settings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('account_id')->index();
            $t->boolean('is_enabled')->default(false);
            $t->decimal('min_purchase_amount', 10, 2)->default(0); // £ needed for 1 stamp
            $t->unsignedTinyInteger('tiers_per_card')->default(1); // 1-5
            $t->unsignedTinyInteger('stamps_per_tier')->default(3); // 3-6
            $t->timestamps();
        });

        Schema::create('loyalty_card_tiers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('loyalty_card_setting_id')->index();
            $t->unsignedTinyInteger('tier_number'); // 1..N
            $t->enum('reward_type', ['percentage','fixed']);
            $t->decimal('reward_value', 10, 2); // percent or fixed amount in £
            $t->timestamps();
        });

        // Optional: customer progress & redemptions
        Schema::create('loyalty_card_ledgers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('account_id')->index();
            $t->unsignedBigInteger('customer_id')->index();
            $t->unsignedInteger('stamps')->default(0); // current card stamp count
            $t->unsignedTinyInteger('current_tier')->default(1);
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('loyalty_card_ledgers');
        Schema::dropIfExists('loyalty_card_tiers');
        Schema::dropIfExists('loyalty_card_settings');
    }
};
