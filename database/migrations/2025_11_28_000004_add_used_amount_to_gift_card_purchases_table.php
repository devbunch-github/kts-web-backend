<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('GiftCardPurchases', function (Blueprint $table) {
            $table->decimal('UsedAmount', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('GiftCardPurchases', function (Blueprint $table) {
            $table->dropColumn('UsedAmount');
        });
    }
};
