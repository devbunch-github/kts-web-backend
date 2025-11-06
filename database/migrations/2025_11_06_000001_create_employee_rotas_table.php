<?php

// database/migrations/2025_11_06_000001_create_employee_rotas_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_rotas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('AccountId')->index();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('shift_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('source')->default('manual'); // manual|regular
            $table->uuid('recurrence_id')->nullable()->index();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('employee_rotas');
    }
};
