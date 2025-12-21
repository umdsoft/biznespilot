<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_report_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            // Report Info
            $table->enum('report_type', [
                'daily_brief', 'weekly_summary', 'monthly_report', 'quarterly_review', 'diagnostic', 'custom'
            ]);
            $table->string('title');
            $table->date('period_start');
            $table->date('period_end');

            // Content
            $table->json('content')->nullable();
            $table->text('summary')->nullable();
            $table->json('highlights')->nullable();

            // Files
            $table->string('html_path', 500)->nullable();
            $table->string('pdf_path', 500)->nullable();

            // Delivery
            $table->json('sent_to')->nullable();
            $table->integer('download_count')->default(0);

            // Metrics
            $table->integer('generation_time_seconds')->nullable();
            $table->integer('ai_tokens_used')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'report_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
