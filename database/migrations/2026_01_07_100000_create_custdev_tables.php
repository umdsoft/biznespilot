<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // CustDev Surveys - So'rovnomalar
        Schema::create('custdev_surveys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('dream_buyer_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique(); // Unikal link uchun: /s/abc123
            $table->string('status')->default('draft'); // draft, active, paused, completed
            $table->string('theme_color')->default('#6366f1'); // Branding uchun
            $table->string('logo_url')->nullable();
            $table->text('welcome_message')->nullable(); // So'rovnoma boshidagi xabar
            $table->text('thank_you_message')->nullable(); // Tugatgandan keyingi xabar
            $table->boolean('collect_contact')->default(false); // Ism/telefon yig'ish
            $table->boolean('anonymous')->default(true); // Anonim javob
            $table->integer('estimated_time')->default(3); // Taxminiy vaqt (minutlarda)
            $table->integer('response_limit')->nullable(); // Maksimal javoblar soni
            $table->timestamp('expires_at')->nullable(); // Muddati
            $table->integer('views_count')->default(0);
            $table->integer('responses_count')->default(0);
            $table->integer('completion_rate')->default(0); // Foizda
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('dream_buyer_id')->references('id')->on('dream_buyers')->onDelete('set null');
            $table->index(['business_id', 'status']);
            $table->index('slug');
        });

        // CustDev Questions - Savollar
        Schema::create('custdev_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('survey_id');
            $table->string('type'); // text, textarea, select, multiselect, rating, scale
            $table->string('category')->nullable(); // where_spend_time, info_sources, frustrations, dreams, fears, custom
            $table->text('question'); // Savol matni
            $table->text('description')->nullable(); // Qo'shimcha izoh
            $table->string('placeholder')->nullable();
            $table->json('options')->nullable(); // Select/multiselect uchun variantlar
            $table->boolean('is_required')->default(true);
            $table->boolean('is_default')->default(false); // Tayyor savol yoki qo'lda qo'shilgan
            $table->integer('order')->default(0);
            $table->string('icon')->nullable();
            $table->json('settings')->nullable(); // Qo'shimcha sozlamalar (min/max, validation)
            $table->timestamps();

            $table->foreign('survey_id')->references('id')->on('custdev_surveys')->onDelete('cascade');
            $table->index(['survey_id', 'order']);
        });

        // CustDev Responses - Javob berganlar
        Schema::create('custdev_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('survey_id');
            $table->string('respondent_name')->nullable();
            $table->string('respondent_phone')->nullable();
            $table->string('respondent_region')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable(); // mobile, desktop, tablet
            $table->string('status')->default('in_progress'); // in_progress, completed, abandoned
            $table->integer('current_question')->default(1);
            $table->integer('time_spent')->default(0); // Sekundlarda
            $table->json('metadata')->nullable(); // UTM, referrer va boshqa ma'lumotlar
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('survey_id')->references('id')->on('custdev_surveys')->onDelete('cascade');
            $table->index(['survey_id', 'status']);
            $table->index('completed_at');
        });

        // CustDev Answers - Alohida javoblar
        Schema::create('custdev_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('response_id');
            $table->uuid('question_id');
            $table->text('answer')->nullable(); // Matnli javob
            $table->json('selected_options')->nullable(); // Select/multiselect uchun
            $table->integer('rating_value')->nullable(); // Rating/scale uchun
            $table->integer('time_spent')->default(0); // Bu savolga sarflangan vaqt
            $table->timestamps();

            $table->foreign('response_id')->references('id')->on('custdev_responses')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('custdev_questions')->onDelete('cascade');
            $table->index(['response_id', 'question_id']);
            $table->unique(['response_id', 'question_id']);
        });

        // CustDev Analytics - Tahlil uchun agregatlangan ma'lumotlar
        Schema::create('custdev_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('survey_id');
            $table->uuid('question_id');
            $table->string('answer_value'); // Javob qiymati
            $table->integer('count')->default(1); // Necha marta tanlangan
            $table->decimal('percentage', 5, 2)->default(0); // Foizi
            $table->timestamps();

            $table->foreign('survey_id')->references('id')->on('custdev_surveys')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('custdev_questions')->onDelete('cascade');
            $table->index(['survey_id', 'question_id']);
            $table->unique(['survey_id', 'question_id', 'answer_value'], 'custdev_analytics_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custdev_analytics');
        Schema::dropIfExists('custdev_answers');
        Schema::dropIfExists('custdev_responses');
        Schema::dropIfExists('custdev_questions');
        Schema::dropIfExists('custdev_surveys');
    }
};
