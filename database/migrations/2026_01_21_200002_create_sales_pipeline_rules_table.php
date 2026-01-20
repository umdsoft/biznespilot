<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pipeline avtomatizatsiya qoidalari jadvali
     */
    public function up(): void
    {
        Schema::create('sales_pipeline_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Trigger
            $table->string('trigger_type', 50); // activity_created, task_completed, time_based, field_changed
            $table->json('trigger_conditions')->nullable(); // {"activity_type": "call"} yoki {"task_type": "meeting", "status": "completed"}

            // Action - pipeline_stages uses bigint, not UUID
            $table->foreignId('from_stage_id')->nullable()->constrained('pipeline_stages')->nullOnDelete();
            $table->foreignId('to_stage_id')->constrained('pipeline_stages')->cascadeOnDelete();

            // Options
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);

            // Statistika
            $table->unsignedInteger('times_triggered')->default(0);
            $table->timestamp('last_triggered_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'is_active', 'priority']);
            $table->index(['trigger_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_pipeline_rules');
    }
};
