<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('Appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('Appointments', 'RefundAmount')) {
                $table->decimal('RefundAmount', 10, 2)->nullable()->after('Deposit');
            }
            if (!Schema::hasColumn('Appointments', 'Discount')) {
                $table->decimal('Discount', 10, 2)->nullable()->after('RefundAmount');
            }
            if (!Schema::hasColumn('Appointments', 'FinalAmount')) {
                $table->decimal('FinalAmount', 10, 2)->nullable()->after('Discount');
            }
            if (!Schema::hasColumn('Appointments', 'EmployeeId')) {
                $table->unsignedBigInteger('EmployeeId')->nullable()->after('ServiceId');
            }
        });
    }

    public function down(): void {
        Schema::table('Appointments', function (Blueprint $table) {
            $table->dropColumn(['RefundAmount', 'Discount', 'FinalAmount']);
        });
    }
};
