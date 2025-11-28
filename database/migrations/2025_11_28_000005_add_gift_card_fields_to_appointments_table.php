<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('Appointments', function (Blueprint $table) {
            // Only add if not already there in DB (safe to run in most envs)
            if (!Schema::hasColumn('Appointments', 'PromoCode')) {
                $table->string('PromoCode', 50)->nullable()->after('Discount');
            }

            if (!Schema::hasColumn('Appointments', 'GiftCardCode')) {
                $table->string('GiftCardCode', 50)->nullable()->after('PromoCode');
            }

            if (!Schema::hasColumn('Appointments', 'GiftCardAmount')) {
                $table->decimal('GiftCardAmount', 10, 2)->default(0)->after('GiftCardCode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('Appointments', function (Blueprint $table) {
            if (Schema::hasColumn('Appointments', 'GiftCardAmount')) {
                $table->dropColumn('GiftCardAmount');
            }
            if (Schema::hasColumn('Appointments', 'GiftCardCode')) {
                $table->dropColumn('GiftCardCode');
            }
            if (Schema::hasColumn('Appointments', 'PromoCode')) {
                $table->dropColumn('PromoCode');
            }
        });
    }
};
