<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();

            // Rule Definition
            $table->string('rule_code', 50)->unique();
            $table->string('rule_name_uz');
            $table->string('rule_name_en')->nullable();
            $table->text('description_uz')->nullable();
            $table->text('description_en')->nullable();

            // Trigger
            $table->enum('alert_type', [
                'threshold', 'metric_change', 'trend', 'goal', 'competitor'
            ]);
            $table->string('metric_code', 50);
            $table->enum('condition', [
                'greater_than', 'less_than', 'equals', 'change_up', 'change_down'
            ]);
            $table->decimal('threshold_value', 15, 2)->nullable();
            $table->decimal('threshold_percent', 5, 2)->nullable();
            $table->enum('comparison_period', ['day', 'week', 'month'])->default('day');

            // Severity
            $table->enum('severity', ['critical', 'high', 'medium', 'low'])->default('medium');

            // Message Template
            $table->text('message_template_uz');
            $table->text('message_template_en')->nullable();
            $table->text('action_suggestion_uz')->nullable();
            $table->text('action_suggestion_en')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
