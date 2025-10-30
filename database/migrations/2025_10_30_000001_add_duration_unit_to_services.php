<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Services', function (Blueprint $table) {
            if (!Schema::hasColumn('Services', 'DurationUnit')) {
                $table->string('DurationUnit', 10)->default('mins')->after('DefaultAppointmentDuration');
            }
        });
    }

    public function down(): void
    {
        Schema::table('Services', function (Blueprint $table) {
            $table->dropColumn('DurationUnit');
        });
    }
};
