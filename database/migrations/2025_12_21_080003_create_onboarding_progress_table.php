<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('current_phase')->default(1);
            $table->string('current_step', 50)->nullable();

            // Phase 1: Data Input
            $table->enum('phase_1_status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->integer('phase_1_completion_percent')->default(0);
            $table->timestamp('phase_1_completed_at')->nullable();

            // Phase 2: AI Diagnostic
            $table->enum('phase_2_status', ['locked', 'ready', 'processing', 'completed'])->default('locked');
            $table->timestamp('phase_2_started_at')->nullable();
            $table->timestamp('phase_2_completed_at')->nullable();

            // Phase 3: Strategy
            $table->enum('phase_3_status', ['locked', 'in_progress', 'completed'])->default('locked');
            $table->timestamp('phase_3_completed_at')->nullable();

            // Phase 4: Launch
            $table->enum('phase_4_status', ['locked', 'ready', 'launched'])->default('locked');
            $table->timestamp('launched_at')->nullable();

            // Overall
            $table->integer('overall_completion_percent')->default(0);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();

            $table->index('current_phase');
            $table->index('phase_1_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_progress');
    }
};
