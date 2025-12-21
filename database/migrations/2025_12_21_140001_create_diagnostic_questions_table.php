<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_questions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('diagnostic_id')->constrained('ai_diagnostics')->onDelete('cascade');

            // Question details
            $table->enum('question_category', ['revenue', 'marketing', 'sales', 'content', 'operations', 'general'])->default('general');
            $table->text('question_text_uz');
            $table->text('question_text_en')->nullable();

            // Context
            $table->json('data_point_referenced')->nullable();
            $table->text('why_asking')->nullable();

            // Answer
            $table->json('answer_options')->nullable();
            $table->text('answer_text')->nullable();
            $table->timestamp('answered_at')->nullable();

            // Impact
            $table->text('impact_on_diagnosis')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->boolean('is_required')->default(false);
            $table->boolean('is_answered')->default(false);

            $table->timestamps();

            $table->index(['diagnostic_id', 'is_answered']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_questions');
    }
};
