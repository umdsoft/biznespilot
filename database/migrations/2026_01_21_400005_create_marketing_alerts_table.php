<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            // Alert type
            $table->string('type', 50);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title');
            $table->text('message');

            // Related entities
            $table->foreignUuid('channel_id')
                ->nullable()
                ->constrained('marketing_channels')
                ->cascadeOnDelete();

            $table->foreignUuid('campaign_id')
                ->nullable()
                ->constrained('campaigns')
                ->cascadeOnDelete();

            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            // Alert data
            $table->json('data')->nullable();
            $table->json('comparison')->nullable();

            // Thresholds
            $table->decimal('threshold_value', 15, 4)->nullable();
            $table->decimal('actual_value', 15, 4)->nullable();
            $table->decimal('deviation_percent', 10, 2)->nullable();

            // Status
            $table->enum('status', ['active', 'acknowledged', 'resolved', 'dismissed'])->default('active');
            $table->foreignUuid('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'status', 'created_at']);
            $table->index(['business_id', 'type', 'severity']);
            $table->index(['business_id', 'campaign_id', 'status']);
            $table->index(['business_id', 'channel_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_alerts');
    }
};
