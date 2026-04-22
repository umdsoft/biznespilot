<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI Jamoa operatsion tizimi jadvallari.
 * Majlislar, agentlararo xabarlar, favqulodda hodisalar, haftalik/oylik rejalar.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Jamoa majlislari
        Schema::create('team_meetings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->enum('meeting_type', ['morning_standup', 'daily_summary', 'weekly_planning', 'monthly_review']);
            $table->date('meeting_date');
            $table->json('agent_reports');
            $table->text('director_summary')->nullable();
            $table->json('urgent_items')->nullable();
            $table->json('action_items')->nullable();
            $table->integer('ai_tokens_used')->default(0);
            $table->string('ai_model_used', 20)->nullable();
            $table->boolean('sent_to_owner')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'meeting_date'], 'team_meetings_business_date_idx');
            $table->index('meeting_type', 'team_meetings_type_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        // Agentlararo xabarlar
        Schema::create('team_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->string('from_agent', 30);
            $table->string('to_agent', 30);
            $table->enum('message_type', ['info', 'request', 'alert', 'response']);
            $table->text('content');
            $table->string('related_entity_type', 50)->nullable();
            $table->uuid('related_entity_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index('business_id', 'team_messages_business_idx');
            $table->index(['to_agent', 'read_at'], 'team_messages_to_agent_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        // Favqulodda hodisalar
        Schema::create('team_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->string('alert_type', 50);
            $table->enum('severity', ['urgent', 'warning', 'info']);
            $table->string('detecting_agent', 30);
            $table->text('message');
            $table->string('action_taken', 100)->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('notified_owner')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'resolved'], 'team_alerts_business_resolved_idx');
            $table->index('severity', 'team_alerts_severity_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        // Haftalik va oylik rejalar
        Schema::create('team_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->enum('plan_type', ['weekly', 'monthly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->json('previous_results');
            $table->json('agent_suggestions');
            $table->json('final_plan')->nullable();
            $table->json('tasks')->nullable();
            $table->integer('ai_tokens_used')->default(0);
            $table->timestamps();
            $table->index(['business_id', 'period_start'], 'team_plans_business_period_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_plans');
        Schema::dropIfExists('team_alerts');
        Schema::dropIfExists('team_messages');
        Schema::dropIfExists('team_meetings');
    }
};
