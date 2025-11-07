<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Forms master
        Schema::create('business_forms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('AccountId')->index();
            $t->string('title');
            $t->enum('frequency', ['once', 'every_booking'])->default('every_booking'); // ask clients to complete...
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        // Questions
        Schema::create('business_form_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('form_id')->constrained('business_forms')->cascadeOnDelete();
            $t->enum('type', ['short_answer', 'description', 'yes_no', 'checkbox']);
            $t->text('label');                 // question text / description
            $t->boolean('required')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
            $t->json('options')->nullable();   // for checkbox (array of labels), future extensibility
            $t->timestamps();
        });

        // Pivot to Services
        Schema::create('business_form_service', function (Blueprint $t) {
            $t->id();
            $t->foreignId('form_id')->constrained('business_forms')->cascadeOnDelete();
            $t->unsignedBigInteger('service_id')->index();
            $t->timestamps();
            $t->unique(['form_id','service_id']);
        });

        // Client submissions (for auditing + “send to customer” links)
        Schema::create('business_form_submissions', function (Blueprint $t) {
            $t->id();
            $t->uuid('AccountId')->index();
            $t->foreignId('form_id')->constrained('business_forms')->cascadeOnDelete();
            $t->unsignedBigInteger('appointment_id')->nullable()->index();
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamps();
        });

        // Answers
        Schema::create('business_form_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('submission_id')->constrained('business_form_submissions')->cascadeOnDelete();
            $t->unsignedBigInteger('question_id');
            $t->foreign('question_id')
                ->references('id')
                ->on('business_form_questions')
                ->onDelete('NO ACTION');
            $t->longText('answer')->nullable(); // store text, “yes/no”, or JSON (for checkbox)
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_form_answers');
        Schema::dropIfExists('business_form_submissions');
        Schema::dropIfExists('business_form_service');
        Schema::dropIfExists('business_form_questions');
        Schema::dropIfExists('business_forms');
    }
};
