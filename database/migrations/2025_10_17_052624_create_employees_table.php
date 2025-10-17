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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('AccountId')->nullable();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('image')->nullable();
            $table->date('start_date')->nullable();
            $table->year('start_year')->nullable();
            $table->date('end_date')->nullable();
            $table->year('end_year')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('modified_by_id')->nullable();
            $table->dateTime('date_created')->nullable();
            $table->dateTime('date_modified')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
