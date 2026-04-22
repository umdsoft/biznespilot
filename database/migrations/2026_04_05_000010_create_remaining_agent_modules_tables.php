<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qolgan 4 modul jadvallari:
 * - Pul oqimi bashoratchi (cash_flow_*)
 * - Obro' boshqaruvchisi (customer_reviews, reputation_scores)
 * - Ovozli yordamchi (voice_interactions)
 * - AI trener (training_sessions, trainee_progress)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== PUL OQIMI =====
        Schema::create('cash_flow_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->decimal('minimum_balance', 15, 2)->default(1000000);
            $table->json('recurring_expenses');
            $table->integer('alert_days_ahead')->default(7);
            $table->timestamps();
            $table->unique('business_id', 'cash_flow_settings_business_unique');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('cash_flow_forecasts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->date('forecast_date');
            $table->decimal('predicted_income', 15, 2);
            $table->decimal('predicted_expense', 15, 2);
            $table->decimal('predicted_balance', 15, 2);
            $table->decimal('confidence_level', 3, 2);
            $table->boolean('is_danger')->default(false);
            $table->timestamps();
            $table->index(['business_id', 'forecast_date'], 'cash_flow_forecasts_business_date_idx');
            $table->index('is_danger', 'cash_flow_forecasts_danger_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('cash_flow_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->date('danger_date');
            $table->decimal('predicted_balance', 15, 2);
            $table->json('recommendations')->nullable();
            $table->enum('status', ['active', 'resolved', 'ignored'])->default('active');
            $table->timestamps();
            $table->index('business_id', 'cash_flow_alerts_business_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        // ===== OBRO' VA SHARHLAR =====
        Schema::create('customer_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->enum('source', ['google_maps', 'instagram', 'telegram', 'manual', 'facebook']);
            $table->string('source_id', 255)->nullable();
            $table->string('reviewer_name', 100)->nullable();
            $table->integer('rating')->nullable();
            $table->text('review_text');
            $table->string('language', 5)->nullable();
            $table->enum('sentiment', ['positive', 'negative', 'neutral', 'mixed']);
            $table->decimal('sentiment_score', 3, 2);
            $table->json('categories')->nullable();
            $table->text('response_text')->nullable();
            $table->enum('response_status', ['pending', 'suggested', 'sent', 'skipped'])->default('pending');
            $table->text('suggested_response')->nullable();
            $table->boolean('flagged')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index('business_id', 'customer_reviews_business_idx');
            $table->index('sentiment', 'customer_reviews_sentiment_idx');
            $table->index('source', 'customer_reviews_source_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('reputation_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('overall_sentiment', 3, 2);
            $table->integer('total_reviews');
            $table->integer('positive_count');
            $table->integer('negative_count');
            $table->integer('neutral_count');
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->decimal('sentiment_trend', 5, 2)->nullable();
            $table->json('top_praise_topics')->nullable();
            $table->json('top_complaint_topics')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'period_start'], 'reputation_scores_business_period_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        // ===== OVOZLI YORDAMCHI =====
        Schema::create('voice_interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->char('user_id', 36);
            $table->uuid('conversation_id')->nullable();
            $table->text('audio_input_url')->nullable();
            $table->integer('audio_input_duration_sec')->nullable();
            $table->text('transcript_text')->nullable();
            $table->string('detected_language', 5)->nullable();
            $table->text('response_text')->nullable();
            $table->text('audio_output_url')->nullable();
            $table->integer('audio_output_duration_sec')->nullable();
            $table->decimal('whisper_cost_usd', 8, 6)->default(0);
            $table->decimal('tts_cost_usd', 8, 6)->default(0);
            $table->decimal('total_cost_usd', 8, 6)->default(0);
            $table->integer('processing_time_ms')->default(0);
            $table->timestamps();
            $table->index('business_id', 'voice_interactions_business_idx');
            $table->index('user_id', 'voice_interactions_user_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        // ===== AI TRENER =====
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->char('trainee_user_id', 36);
            $table->char('trainer_user_id', 36)->nullable();
            $table->enum('session_type', ['greeting', 'presentation', 'objection', 'closing', 'full']);
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->json('messages');
            $table->integer('overall_score')->nullable();
            $table->json('stage_scores')->nullable();
            $table->json('strengths')->nullable();
            $table->json('improvements')->nullable();
            $table->string('recommended_next_session', 50)->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('ai_tokens_used')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index('business_id', 'training_sessions_business_idx');
            $table->index('trainee_user_id', 'training_sessions_trainee_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('trainee_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->char('user_id', 36);
            $table->integer('total_sessions')->default(0);
            $table->decimal('avg_score', 5, 2)->default(0);
            $table->integer('best_score')->default(0);
            $table->string('weakest_area', 50)->nullable();
            $table->string('strongest_area', 50)->nullable();
            $table->boolean('ready_for_live')->default(false);
            $table->timestamp('last_session_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'user_id'], 'trainee_progress_business_user_unique');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainee_progress');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('voice_interactions');
        Schema::dropIfExists('reputation_scores');
        Schema::dropIfExists('customer_reviews');
        Schema::dropIfExists('cash_flow_alerts');
        Schema::dropIfExists('cash_flow_forecasts');
        Schema::dropIfExists('cash_flow_settings');
    }
};
