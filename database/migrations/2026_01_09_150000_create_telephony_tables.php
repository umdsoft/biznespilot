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
        // PBX Accounts
        Schema::create('pbx_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name')->default('PBX');
            $table->string('api_url');
            $table->string('api_key');
            $table->string('api_secret')->nullable();
            $table->string('caller_id')->nullable(); // Default caller ID
            $table->string('extension')->nullable(); // Default extension
            $table->boolean('is_active')->default(true);
            $table->integer('balance')->default(0);
            $table->json('settings')->nullable(); // Additional settings
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'is_active']);
        });

        // SipUni Accounts
        Schema::create('sipuni_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name')->default('SipUni');
            $table->string('api_key');
            $table->string('api_secret');
            $table->string('caller_id')->nullable();
            $table->string('callback_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('balance')->default(0);
            $table->json('settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'is_active']);
        });

        // Call Logs
        Schema::create('call_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('lead_id')->nullable();
            $table->uuid('user_id')->nullable(); // Who initiated the call
            $table->string('provider'); // pbx, sipuni
            $table->string('provider_call_id')->nullable(); // External call ID
            $table->string('direction'); // inbound, outbound
            $table->string('from_number');
            $table->string('to_number');
            $table->string('status'); // initiated, ringing, answered, completed, failed, missed, busy, no_answer
            $table->integer('duration')->default(0); // seconds
            $table->integer('wait_time')->default(0); // seconds before answer
            $table->string('recording_url')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional data from provider
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'created_at']);
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'direction']);
            $table->index(['lead_id']);
            $table->index('provider_call_id');
        });

        // Call Daily Stats
        Schema::create('call_daily_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->date('stat_date');
            $table->integer('total_calls')->default(0);
            $table->integer('outbound_calls')->default(0);
            $table->integer('inbound_calls')->default(0);
            $table->integer('answered_calls')->default(0);
            $table->integer('missed_calls')->default(0);
            $table->integer('failed_calls')->default(0);
            $table->integer('total_duration')->default(0); // total seconds
            $table->integer('avg_duration')->default(0); // average seconds
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'stat_date']);
        });

        // Auto Dialer Campaigns
        Schema::create('auto_dialer_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('created_by');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, scheduled, running, paused, completed, cancelled
            $table->string('caller_id');
            $table->integer('calls_per_minute')->default(1);
            $table->integer('max_attempts')->default(3);
            $table->integer('retry_delay')->default(60); // minutes between retries
            $table->time('start_time')->nullable(); // Daily start time
            $table->time('end_time')->nullable(); // Daily end time
            $table->json('working_days')->nullable(); // [1,2,3,4,5] Monday-Friday
            $table->string('audio_file')->nullable(); // Pre-recorded message
            $table->text('script')->nullable(); // Call script for agents
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['business_id', 'status']);
        });

        // Auto Dialer Campaign Leads
        Schema::create('auto_dialer_campaign_leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->uuid('lead_id');
            $table->string('status')->default('pending'); // pending, calling, completed, failed, skipped
            $table->integer('attempts')->default(0);
            $table->string('last_result')->nullable(); // answered, no_answer, busy, failed
            $table->integer('last_duration')->default(0);
            $table->timestamp('last_called_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('auto_dialer_campaigns')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->unique(['campaign_id', 'lead_id']);
            $table->index(['campaign_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_dialer_campaign_leads');
        Schema::dropIfExists('auto_dialer_campaigns');
        Schema::dropIfExists('call_daily_stats');
        Schema::dropIfExists('call_logs');
        Schema::dropIfExists('sipuni_accounts');
        Schema::dropIfExists('pbx_accounts');
    }
};
