<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reports
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('created_by');
            $table->string('name');
            $table->string('type', 50);
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->json('filters')->nullable();
            $table->json('data')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('business_id');
            $table->index('type');
            $table->softDeletes();
        });

        // Goals
        Schema::create('goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->string('type', 50);
            $table->string('metric', 50);
            $table->decimal('target_value', 14, 2);
            $table->decimal('current_value', 14, 2)->default(0);
            $table->string('period', 20); // daily, weekly, monthly, quarterly, yearly
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('active');
            $table->integer('progress_percentage')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('status');
            $table->index(['business_id', 'period']);
            $table->softDeletes();
        });

        // Alerts
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('type', 50);
            $table->string('category', 50)->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('severity', 20)->default('info'); // info, warning, error, critical
            $table->string('status', 20)->default('active');
            $table->json('data')->nullable();
            $table->json('action_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('type');
            $table->index('severity');
            $table->index(['business_id', 'is_read']);
            $table->softDeletes();
        });

        // Alert Rules
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->string('metric', 100);
            $table->string('condition', 20); // gt, lt, eq, gte, lte
            $table->decimal('threshold', 14, 2);
            $table->string('severity', 20)->default('warning');
            $table->json('notification_channels')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
        });

        // Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id')->nullable();
            $table->string('type', 50);
            $table->string('action', 50);
            $table->string('subject_type')->nullable();
            $table->uuid('subject_id')->nullable();
            $table->text('description')->nullable();
            $table->json('properties')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index('business_id');
            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['subject_type', 'subject_id']);
        });

        // KPI Calculations
        Schema::create('kpi_calculations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('kpi_type', 50);
            $table->date('calculation_date');
            $table->decimal('value', 14, 4);
            $table->decimal('previous_value', 14, 4)->nullable();
            $table->decimal('change_percentage', 8, 2)->nullable();
            $table->json('breakdown')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'kpi_type', 'calculation_date']);
            $table->index(['business_id', 'calculation_date']);
        });

        // KPI Daily Snapshots
        Schema::create('kpi_daily_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->date('snapshot_date');
            $table->decimal('revenue', 14, 2)->default(0);
            $table->decimal('expenses', 14, 2)->default(0);
            $table->decimal('profit', 14, 2)->default(0);
            $table->integer('new_leads')->default(0);
            $table->integer('new_customers')->default(0);
            $table->integer('orders_count')->default(0);
            $table->decimal('average_order_value', 12, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->integer('website_visitors')->default(0);
            $table->json('channel_breakdown')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'snapshot_date']);
            $table->index('snapshot_date');
        });

        // KPI Targets
        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('kpi_type', 50);
            $table->string('period', 20);
            $table->integer('year');
            $table->integer('period_number'); // month/quarter number
            $table->decimal('target_value', 14, 2);
            $table->decimal('actual_value', 14, 2)->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'kpi_type', 'period', 'year', 'period_number'], 'kpi_targets_unique');
        });

        // Dashboard Widgets
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->string('widget_type', 50);
            $table->string('title');
            $table->json('config')->nullable();
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('width')->default(1);
            $table->integer('height')->default(1);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['business_id', 'user_id']);
        });

        // Scheduled Reports
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('created_by');
            $table->string('name');
            $table->string('report_type', 50);
            $table->string('frequency', 20); // daily, weekly, monthly
            $table->json('config')->nullable();
            $table->json('recipients')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('business_id');
        });

        // Generated Reports
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('scheduled_report_id')->nullable();
            $table->uuid('business_id');
            $table->string('report_type', 50);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('format', 10)->default('pdf');
            $table->integer('file_size')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->foreign('scheduled_report_id')->references('id')->on('scheduled_reports')->onDelete('set null');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('kpi_targets');
        Schema::dropIfExists('kpi_daily_snapshots');
        Schema::dropIfExists('kpi_calculations');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('alert_rules');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('reports');
    }
};
