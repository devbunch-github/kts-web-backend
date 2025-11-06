<?php

// database/migrations/2025_11_06_000002_add_account_and_recurrence_to_employee_time_offs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('employee_time_offs', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_time_offs','AccountId')) {
                $table->unsignedBigInteger('AccountId')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('employee_time_offs','recurrence_id')) {
                $table->uuid('recurrence_id')->nullable()->after('is_repeat')->index();
            }
        });
    }

    public function down(): void {
        Schema::table('employee_time_offs', function (Blueprint $table) {
            $table->dropColumn(['AccountId','recurrence_id']);
        });
    }
};
