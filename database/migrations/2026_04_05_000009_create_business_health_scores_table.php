<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biznes sog'ligi monitori, Mijoz umr yo'li, Mavsumiy kalendar jadvallari.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Biznes sog'ligi ballari
        Schema::create('business_health_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('overall_score');           // 0-100
            $table->integer('marketing_score');
            $table->json('marketing_details');
            $table->integer('sales_score');
            $table->json('sales_details');
            $table->integer('finance_score');
            $table->json('finance_details');
            $table->integer('customer_score');
            $table->json('customer_details');
            $table->integer('previous_overall_score')->nullable();
            $table->integer('change_from_previous')->nullable();
            $table->json('top_issues')->nullable();
            $table->json('recommendations')->nullable();
            $table->integer('ai_tokens_used')->default(0);
            $table->timestamps();

            $table->index(['business_id', 'period_start'], 'business_health_scores_period_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        // Mijoz umr yo'li
        Schema::create('customer_lifecycle', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->char('customer_id', 36);
            $table->enum('current_stage', [
                'new', 'interested', 'first_purchase', 'feedback',
                'repeat', 'loyal', 'churning', 'win_back', 'birthday',
            ])->default('new');
            $table->string('previous_stage', 30)->nullable();
            $table->timestamp('stage_entered_at');
            $table->timestamp('next_action_at')->nullable();
            $table->string('next_action_type', 50)->nullable();
            $table->integer('total_purchases')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->timestamp('last_purchase_at')->nullable();
            $table->date('birthday')->nullable();
            $table->enum('preferred_channel', ['telegram', 'instagram', 'facebook'])->nullable();
            $table->integer('lifecycle_score')->default(0);
            $table->timestamps();

            $table->index('business_id', 'customer_lifecycle_business_idx');
            $table->index('current_stage', 'customer_lifecycle_stage_idx');
            $table->index('next_action_at', 'customer_lifecycle_next_action_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        // Umr yo'li harakatlari
        Schema::create('lifecycle_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->char('customer_id', 36);
            $table->uuid('lifecycle_id');
            $table->string('stage', 30);
            $table->string('action_type', 50);
            $table->enum('channel', ['telegram', 'instagram', 'facebook', 'sms']);
            $table->uuid('message_template_id')->nullable();
            $table->text('message_content')->nullable();
            $table->boolean('personalized_by_ai')->default(false);
            $table->enum('status', ['scheduled', 'sent', 'delivered', 'opened', 'clicked', 'converted'])->default('scheduled');
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->string('result_action', 50)->nullable();
            $table->timestamps();

            $table->index('business_id', 'lifecycle_actions_business_idx');
            $table->index(['scheduled_at', 'status'], 'lifecycle_actions_scheduled_idx');
            $table->foreign('lifecycle_id')->references('id')->on('customer_lifecycle')->cascadeOnDelete();
        });

        // Mavsumiy kalendar
        Schema::create('local_calendar', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_name', 100);
            $table->enum('event_type', ['national_holiday', 'religious', 'seasonal', 'commercial', 'education']);
            $table->string('fixed_date', 10)->nullable();  // '03-08'
            $table->boolean('is_lunar')->default(false);
            $table->integer('typical_month')->nullable();
            $table->date('year_date')->nullable();
            $table->integer('preparation_days')->default(14);
            $table->json('impact_industries');
            $table->text('impact_description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_calendar');
        Schema::dropIfExists('lifecycle_actions');
        Schema::dropIfExists('customer_lifecycle');
        Schema::dropIfExists('business_health_scores');
    }
};
