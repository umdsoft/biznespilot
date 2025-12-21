<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            // Platform enum - adding more options
            $table->enum('platform', [
                'instagram', 'telegram_channel', 'telegram_bot',
                'amocrm', 'google_ads', 'facebook_ads',
                'google_analytics', 'click', 'payme', 'meta_ads'
            ])->nullable()->after('type');

            // Account details
            $table->string('account_id')->nullable()->after('platform');
            $table->string('account_name')->nullable()->after('account_id');
            $table->string('account_username')->nullable()->after('account_name');

            // Metrics
            $table->integer('followers_count')->nullable()->after('account_username');
            $table->integer('posts_count')->nullable()->after('followers_count');

            // Sync settings
            $table->enum('sync_frequency', ['realtime', 'hourly', 'daily'])->default('daily')->after('posts_count');
            $table->date('data_from_date')->nullable()->after('sync_frequency');

            // Onboarding required flag
            $table->boolean('is_required_for_onboarding')->default(false)->after('data_from_date');

            // Error tracking
            $table->integer('error_count')->default(0)->after('last_error_message');
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn([
                'platform', 'account_id', 'account_name', 'account_username',
                'followers_count', 'posts_count', 'sync_frequency',
                'data_from_date', 'is_required_for_onboarding', 'error_count'
            ]);
        });
    }
};
