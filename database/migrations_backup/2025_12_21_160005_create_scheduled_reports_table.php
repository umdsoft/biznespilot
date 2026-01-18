<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            // Report Config
            $table->enum('report_type', [
                'daily_brief', 'weekly_summary', 'monthly_report', 'quarterly_review', 'custom',
            ]);
            $table->string('report_name');

            // Schedule
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly']);
            $table->integer('day_of_week')->nullable(); // 1-7 for weekly
            $table->integer('day_of_month')->nullable(); // 1-31 for monthly
            $table->time('time_of_day')->default('09:00:00');
            $table->string('timezone', 50)->default('Asia/Tashkent');

            // Content
            $table->json('sections')->nullable(); // ['executive_summary', 'kpis', 'channels', etc.]
            $table->boolean('include_charts')->default(true);
            $table->boolean('include_comparison')->default(true);
            $table->enum('comparison_period', ['previous_period', 'same_period_last_year'])->default('previous_period');

            // Delivery
            $table->enum('delivery_method', ['email', 'download', 'both'])->default('email');
            $table->json('recipients')->nullable(); // [{email: '', name: ''}]
            $table->enum('format', ['pdf', 'html', 'both'])->default('pdf');

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_scheduled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
