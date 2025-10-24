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
        Schema::create('accountants', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('created_by')->nullable(); // Business admin (creator)
            $t->unsignedBigInteger('AccountId')->index();
            $t->string('name');
            $t->string('email');
            $t->string('password')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();

            $t->unique(['AccountId', 'email']);

            // Optional FK relationships
            $t->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accountants');
    }
};