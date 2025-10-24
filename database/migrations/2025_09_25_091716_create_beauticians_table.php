<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('beauticians', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('subdomain')->unique()->nullable();

            // Business info fields
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->json('services')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover')->nullable();

            $table->string('location')->nullable();
            $table->string('category')->nullable();

            $table->decimal('rating', 2, 1)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauticians');
    }
};
