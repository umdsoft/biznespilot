<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_hypotheses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->enum('hypothesis_type', ['channel', 'content', 'offer', 'audience', 'funnel']);

            // Hypothesis structure: If X, Then Y, Because Z
            $table->text('if_statement');
            $table->text('then_statement');
            $table->text('because_statement');

            // Testing
            $table->enum('test_method', ['a_b_test', 'pilot', 'survey', 'mvp'])->nullable();
            $table->string('success_metric');
            $table->decimal('target_value', 15, 2)->nullable();
            $table->decimal('baseline_value', 15, 2)->nullable();
            $table->integer('test_duration_days')->nullable();
            $table->integer('sample_size_needed')->nullable();

            // Status & results
            $table->enum('status', ['draft', 'testing', 'validated', 'invalidated', 'paused'])->default('draft');
            $table->enum('confidence_level', ['low', 'medium', 'high'])->nullable();
            $table->decimal('actual_result', 15, 2)->nullable();
            $table->date('result_date')->nullable();
            $table->text('learnings')->nullable();
            $table->text('next_steps')->nullable();

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'hypothesis_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_hypotheses');
    }
};
