<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_email_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->unsignedBigInteger('email_template_id')->index();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('status')->default(true);
            $table->string('logo_url')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'email_template_id']);
            $table->foreign('account_id')->references('Id')->on('accounts')->onDelete('cascade');
            $table->foreign('email_template_id')->references('id')->on('email_templates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_email_templates');
    }
};
