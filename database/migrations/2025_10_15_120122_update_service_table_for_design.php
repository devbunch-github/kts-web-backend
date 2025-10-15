<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Services', function (Blueprint $table) {
            if (!Schema::hasColumn('Services', 'CategoryId')) {
                $table->unsignedBigInteger('CategoryId')->nullable()->after('AccountId');
                // 🔧 Changed from cascade to set null
                $table->foreign('CategoryId')
                      ->references('id')
                      ->on('categories')
                      ->onDelete('set null');
            }

            if (!Schema::hasColumn('Services', 'Description')) {
                $table->text('Description')->nullable()->after('DefaultAppointmentDuration');
            }

            if (!Schema::hasColumn('Services', 'FilePath')) {
                $table->string('FilePath')->nullable()->after('Description');
            }

            if (!Schema::hasColumn('Services', 'ImagePath')) {
                $table->string('ImagePath')->nullable()->after('FilePath');
            }

            if (!Schema::hasColumn('Services', 'IsDeleted')) {
                $table->boolean('IsDeleted')->default(0)->after('DateModified');
            }
        });
    }

    public function down(): void
    {
        Schema::table('Services', function (Blueprint $table) {
            $table->dropForeign(['CategoryId']);
            $table->dropColumn(['CategoryId','Description','FilePath','ImagePath','IsDeleted']);
        });
    }
};
