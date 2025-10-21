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
        Schema::create('customer_reviews', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('AccountId')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('service_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('service_name')->nullable();
            $t->tinyInteger('rating')->default(0); // 1–5 scale
            $t->text('review')->nullable();
            $t->boolean('status')->default(1); // 1=Active, 0=Inactive
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_reviews');
    }
};