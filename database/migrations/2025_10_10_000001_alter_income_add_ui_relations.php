<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('Income', function (Blueprint $table) {
            // Add new columns
            $table->unsignedBigInteger('CustomerId')->nullable()->after('AccountingPeriodId');
            $table->unsignedBigInteger('CategoryId')->nullable()->after('CustomerId');
            $table->unsignedBigInteger('ServiceId')->nullable()->after('CategoryId');

            $table->boolean('IsRefund')->default(false)->after('Amount');
            $table->decimal('RefundAmount', 10, 2)->nullable()->after('IsRefund');
            $table->text('Notes')->nullable()->after('Description');
            $table->unsignedBigInteger('CreatedBy')->nullable()->after('Notes');
            $table->unsignedBigInteger('UpdatedBy')->nullable()->after('CreatedBy');

            // Optional column rename if exists
            if (!Schema::hasColumn('Income', 'PaymentMethod') && Schema::hasColumn('Income', 'PaymentMehod')) {
                $table->renameColumn('PaymentMehod', 'PaymentMethod');
            }

            // Foreign keys - use NO ACTION to avoid multiple cascade paths
            $table->foreign('CategoryId')
                  ->references('Id')
                  ->on('categories')
                  ->onDelete('no action')
                  ->onUpdate('no action');

            $table->foreign('ServiceId')
                  ->references('Id')
                  ->on('services')
                  ->onDelete('no action')
                  ->onUpdate('no action');

            // $table->foreign('CustomerId')
            //       ->references('Id')
            //       ->on('customers')
            //       ->onDelete('no action')
            //       ->onUpdate('no action');
        });
    }

    public function down(): void
    {
        Schema::table('Income', function (Blueprint $table) {
            $table->dropForeign(['CategoryId']);
            $table->dropForeign(['ServiceId']);
            // $table->dropForeign(['CustomerId']);

            $table->dropColumn([
                'IsRefund',
                'RefundAmount',
                'Notes',
                'CreatedBy',
                'UpdatedBy',
                'CustomerId',
                'CategoryId',
                'ServiceId'
            ]);
        });
    }
};
