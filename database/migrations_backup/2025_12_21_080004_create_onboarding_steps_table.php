<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('step_definition_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->integer('completion_percent')->default(0);
            $table->json('validation_errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'step_definition_id']);
            $table->index(['business_id', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_steps');
    }
};
