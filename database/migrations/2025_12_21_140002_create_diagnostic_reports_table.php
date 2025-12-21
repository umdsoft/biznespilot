<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('diagnostic_id')->constrained('ai_diagnostics')->onDelete('cascade');

            // Report type
            $table->enum('report_type', ['summary', 'detailed', 'executive'])->default('summary');
            $table->enum('report_format', ['html', 'pdf', 'json'])->default('html');

            // Content
            $table->string('title');
            $table->json('content')->nullable();
            $table->longText('html_content')->nullable();
            $table->string('pdf_path', 500)->nullable();

            // Delivery
            $table->string('sent_to_email')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);

            $table->timestamps();

            $table->index('diagnostic_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_reports');
    }
};
