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
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users');   // always valid user
            $t->foreignId('plan_id')->constrained('plans');
            $t->string('status')->default('pending'); // pending|active|canceled
            $t->string('payment_provider')->nullable();
            $t->string('payment_reference')->nullable();
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
