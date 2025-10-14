<?php

// database/migrations/2025_10_10_000001_alter_income_add_ui_relations.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('Income', function (Blueprint $table) {
            // Foreign keys (nullable to keep it flexible)
            $table->unsignedBigInteger('CustomerId')->nullable()->after('AccountingPeriodId');
            $table->unsignedBigInteger('CategoryId')->nullable()->after('CustomerId');
            $table->unsignedBigInteger('ServiceId')->nullable()->after('CategoryId');

            // Refund support
            $table->boolean('IsRefund')->default(false)->after('Amount');
            $table->decimal('RefundAmount', 10, 2)->nullable()->after('IsRefund');

            // Notes (maps to “Income Description” in UI)
            $table->text('Notes')->nullable()->after('Description');

            // auditing
            $table->unsignedBigInteger('CreatedBy')->nullable()->after('Notes');
            $table->unsignedBigInteger('UpdatedBy')->nullable()->after('CreatedBy');

            // fix spelling if needed (you currently have PaymentMehod)
            if (!Schema::hasColumn('Income', 'PaymentMethod') && Schema::hasColumn('Income', 'PaymentMehod')) {
                $table->renameColumn('PaymentMehod', 'PaymentMethod');
            }

            // FKs (assumes tables exist)
            $table->foreign('CategoryId')->references('Id')->on('categories')->nullOnDelete();
            $table->foreign('ServiceId')->references('Id')->on('services')->nullOnDelete();
            // optional:
            // $table->foreign('CustomerId')->references('Id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('Income', function (Blueprint $table) {
            $table->dropConstrainedForeignId('CategoryId');
            $table->dropConstrainedForeignId('ServiceId');
            // $table->dropConstrainedForeignId('CustomerId');
            $table->dropColumn(['IsRefund','RefundAmount','Notes','CreatedBy','UpdatedBy','CustomerId','CategoryId','ServiceId']);
            // PaymentMethod rename rollback intentionally omitted
        });
    }
};
