<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operatorlar uchun avtomatik coaching vazifalari.
 * Ball past bo'lsa — avtomatik yaratiladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_coaching_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->index();
            $table->uuid('operator_id')->index();
            $table->uuid('call_analysis_id')->nullable()->index();

            $table->string('title');
            $table->text('description');
            $table->enum('weak_area', [
                'greeting', 'discovery', 'presentation',
                'objection_handling', 'closing', 'rapport', 'cta',
                'script_compliance', 'talk_ratio', 'sentiment',
            ]);
            $table->decimal('score_at_creation', 5, 2)->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();

            $table->timestamps();

            $table->index(['operator_id', 'status']);
            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_coaching_tasks');
    }
};
