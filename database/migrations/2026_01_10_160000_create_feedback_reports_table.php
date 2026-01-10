<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('business_id')->nullable();
            $table->string('type'); // bug, suggestion, question, other
            $table->string('title');
            $table->text('description');
            $table->string('status')->default('pending'); // pending, in_progress, resolved, closed
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->text('admin_notes')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('page_url')->nullable(); // Where the feedback was submitted from
            $table->string('browser_info')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['status', 'created_at']);
            $table->index(['type', 'status']);
            $table->index('user_id');
        });

        Schema::create('feedback_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('feedback_report_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->timestamps();

            $table->foreign('feedback_report_id')->references('id')->on('feedback_reports')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_attachments');
        Schema::dropIfExists('feedback_reports');
    }
};
