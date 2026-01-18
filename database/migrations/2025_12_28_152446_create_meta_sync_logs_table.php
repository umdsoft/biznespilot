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
        if (! Schema::hasTable('meta_sync_logs')) {
            Schema::create('meta_sync_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('business_id');
                $table->uuid('integration_id');

                // Sync Info
                $table->enum('sync_type', ['full', 'incremental', 'campaigns', 'insights', 'demographics', 'placements']);
                $table->enum('status', ['started', 'in_progress', 'completed', 'failed']);

                // Progress
                $table->integer('total_items')->default(0);
                $table->integer('processed_items')->default(0);
                $table->integer('failed_items')->default(0);

                // Timing
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->integer('duration_seconds')->nullable();

                // Errors
                $table->text('error_message')->nullable();
                $table->json('error_details')->nullable();

                $table->timestamps();

                // Indexes
                $table->index('business_id');
                $table->index('status');
                $table->index(['business_id', 'sync_type', 'status']);

                // Foreign keys
                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
                $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_sync_logs');
    }
};
