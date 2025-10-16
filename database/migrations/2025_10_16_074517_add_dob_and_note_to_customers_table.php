<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Customers', function (Blueprint $table) {
            $table->date('DateOfBirth')->nullable()->after('Email');
            $table->text('Note')->nullable()->after('DateOfBirth');
        });
    }

    public function down(): void
    {
        Schema::table('Customers', function (Blueprint $table) {
            $table->dropColumn(['DateOfBirth', 'Note']);
        });
    }
};
