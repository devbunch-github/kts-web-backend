<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('business_todos', function (Blueprint $table) {
            // 🚀 Tell SQL Server this UUID column is NOT identity/autoincrement
            $table->bigIncrements('id');

            $table->unsignedBigInteger('AccountId')->index();  // numeric tenant scope
            $table->string('title');
            $table->dateTime('due_datetime')->nullable();
            $table->boolean('is_completed')->default(false);

            $table->unsignedBigInteger('CreatedById')->nullable()->index();
            $table->unsignedBigInteger('ModifiedById')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_todos');
    }
};
