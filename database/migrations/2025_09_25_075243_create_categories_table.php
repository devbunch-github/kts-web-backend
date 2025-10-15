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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('Name');
            $table->string('Description')->nullable();
            $table->unsignedBigInteger('AccountId');
            $table->unsignedBigInteger('CreatedById')->nullable();
            $table->boolean('IsActive')->default(true);
            $table->timestamps();

            $table->foreign('AccountId')
                ->references('Id')
                ->on('accounts')
                ->cascadeOnDelete();

            $table->foreign('CreatedById')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
