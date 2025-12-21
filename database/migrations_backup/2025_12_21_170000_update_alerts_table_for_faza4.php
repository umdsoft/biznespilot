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
        Schema::table('alerts', function (Blueprint $table) {
            // Add new columns for FAZA 4
            $table->foreignId('alert_rule_id')->nullable()->after('user_id')->constrained('alert_rules')->nullOnDelete();
            $table->string('alert_type')->nullable()->after('type'); // threshold, metric_change, trend, goal, competitor, system
            $table->string('alert_category')->nullable()->after('alert_type'); // revenue, leads, marketing, sales, content, funnel, competitor, system
            $table->string('metric_code')->nullable()->after('alert_category');
            $table->string('condition')->nullable()->after('metric_code'); // greater_than, less_than, equals, change_up, change_down, anomaly
            $table->decimal('threshold_value', 15, 2)->nullable()->after('condition');
            $table->decimal('threshold_percent', 8, 2)->nullable()->after('threshold_value');
            $table->string('comparison_period')->nullable()->after('threshold_percent'); // day, week, month
            $table->decimal('current_value', 15, 2)->nullable()->after('comparison_period');
            $table->decimal('previous_value', 15, 2)->nullable()->after('current_value');
            $table->decimal('change_percent', 8, 2)->nullable()->after('previous_value');
            $table->text('action_suggestion')->nullable()->after('message');
            $table->string('status')->default('new')->after('severity'); // new, acknowledged, resolved, snoozed, dismissed
            $table->timestamp('acknowledged_at')->nullable()->after('triggered_at');
            $table->unsignedBigInteger('acknowledged_by')->nullable()->after('acknowledged_at');
            $table->timestamp('resolved_at')->nullable()->after('acknowledged_by');
            $table->text('resolution_note')->nullable()->after('resolved_at');
            $table->timestamp('snoozed_until')->nullable()->after('resolution_note');
            $table->boolean('notify_in_app')->default(true)->after('snoozed_until');
            $table->boolean('notify_email')->default(true)->after('notify_in_app');
            $table->boolean('notify_telegram')->default(false)->after('notify_email');
            $table->boolean('notify_sms')->default(false)->after('notify_telegram');
            $table->integer('cooldown_hours')->default(24)->after('notify_sms');
            $table->timestamp('last_triggered_at')->nullable()->after('cooldown_hours');
            $table->boolean('is_active')->default(true)->after('last_triggered_at');

            // Add indexes
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'alert_category']);
            $table->index(['alert_rule_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status']);
            $table->dropIndex(['business_id', 'alert_category']);
            $table->dropIndex(['alert_rule_id']);

            $table->dropColumn([
                'alert_rule_id',
                'alert_type',
                'alert_category',
                'metric_code',
                'condition',
                'threshold_value',
                'threshold_percent',
                'comparison_period',
                'current_value',
                'previous_value',
                'change_percent',
                'action_suggestion',
                'status',
                'acknowledged_at',
                'acknowledged_by',
                'resolved_at',
                'resolution_note',
                'snoozed_until',
                'notify_in_app',
                'notify_email',
                'notify_telegram',
                'notify_sms',
                'cooldown_hours',
                'last_triggered_at',
                'is_active',
            ]);
        });
    }
};
