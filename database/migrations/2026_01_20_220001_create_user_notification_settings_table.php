<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            // Telegram settings
            $table->boolean('telegram_enabled')->default(false);
            $table->string('telegram_chat_id')->nullable();
            $table->boolean('telegram_alerts')->default(true);
            $table->boolean('telegram_insights')->default(false);
            $table->boolean('telegram_reports')->default(false);
            $table->boolean('telegram_kpi')->default(true);
            $table->boolean('telegram_tasks')->default(true);
            $table->boolean('telegram_leads')->default(true);

            // Email settings
            $table->boolean('email_enabled')->default(true);
            $table->boolean('email_alerts')->default(true);
            $table->boolean('email_insights')->default(true);
            $table->boolean('email_reports')->default(true);
            $table->boolean('email_kpi')->default(true);
            $table->boolean('email_tasks')->default(false);
            $table->boolean('email_leads')->default(false);
            $table->boolean('email_digest_daily')->default(false);
            $table->boolean('email_digest_weekly')->default(true);

            // In-app settings
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('in_app_alerts')->default(true);
            $table->boolean('in_app_insights')->default(true);
            $table->boolean('in_app_reports')->default(true);
            $table->boolean('in_app_system')->default(true);

            // Quiet hours
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'business_id']);
        });

        // Notification delivery log
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel'); // telegram, email, in_app
            $table->string('type'); // alert, insight, report, kpi, task, lead
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->string('status')->default('pending'); // pending, sent, failed, delivered, read
            $table->text('error_message')->nullable();
            $table->string('external_id')->nullable(); // Telegram message_id, email message_id
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'channel', 'status']);
            $table->index(['user_id', 'channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('user_notification_settings');
    }
};
