<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index();
            $table->unsignedBigInteger('created_by_id')->nullable()->index();
            $table->unsignedBigInteger('modified_by_id')->nullable()->index();

            $table->string('title', 191);
            $table->string('code', 50);
            $table->unsignedBigInteger('service_id')->nullable()->index(); // null => all services

            $table->enum('discount_type', ['percent', 'fixed'])->default('percent');
            $table->decimal('discount_value', 10, 2);

            $table->date('start_date');
            $table->date('end_date')->nullable(); // optional end
            $table->tinyInteger('status')->default(1); // 1=Active, 0=Inactive

            $table->text('notes')->nullable();

            // audit style you use elsewhere
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_modified')->nullable();

            $table->softDeletes(); // deleted_at

            $table->unique(['account_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
