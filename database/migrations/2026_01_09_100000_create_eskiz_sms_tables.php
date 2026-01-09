<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates tables for Eskiz.uz SMS integration:
     * - eskiz_accounts: Business SMS account configuration
     * - sms_templates: Reusable SMS templates
     * - sms_messages: SMS sending log
     * - sms_daily_stats: Daily statistics
     */
    public function up(): void
    {
        // Eskiz Account Configuration (per business)
        Schema::create('eskiz_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('email');
            $table->text('password'); // Encrypted
            $table->string('sender_name', 11); // Max 11 chars for SMS sender
            $table->text('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('balance')->default(0);
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id']);
            $table->index('is_active');
        });

        // SMS Templates
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->text('content'); // Template with placeholders like {name}, {phone}
            $table->string('category')->nullable(); // sales, support, notification
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'is_active']);
        });

        // SMS Messages Log
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('eskiz_account_id');
            $table->uuid('lead_id')->nullable();
            $table->uuid('sent_by');
            $table->uuid('template_id')->nullable();
            $table->string('phone', 20);
            $table->text('message');
            $table->string('eskiz_message_id')->nullable();
            $table->string('status', 20)->default('pending'); // pending, sent, delivered, failed
            $table->integer('parts_count')->default(1);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('eskiz_account_id')->references('id')->on('eskiz_accounts')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('sent_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('sms_templates')->onDelete('set null');

            $table->index(['business_id', 'created_at']);
            $table->index(['lead_id']);
            $table->index(['status']);
            $table->index(['sent_by']);
        });

        // SMS Daily Statistics
        Schema::create('sms_daily_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->date('stat_date');
            $table->integer('total_sent')->default(0);
            $table->integer('delivered')->default(0);
            $table->integer('failed')->default(0);
            $table->integer('pending')->default(0);
            $table->integer('parts_used')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'stat_date']);
            $table->index('stat_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_daily_stats');
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('sms_templates');
        Schema::dropIfExists('eskiz_accounts');
    }
};
