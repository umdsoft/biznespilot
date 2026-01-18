<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_problems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->enum('problem_category', [
                'revenue', 'leads', 'conversion', 'retention', 'awareness', 'other',
            ]);
            $table->text('problem_description');
            $table->enum('impact_level', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->date('when_started')->nullable();
            $table->json('attempts_to_solve')->nullable();
            $table->text('desired_outcome')->nullable();
            $table->text('success_metrics')->nullable();
            $table->integer('priority')->default(1);
            $table->enum('status', ['active', 'resolved', 'monitoring'])->default('active');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'problem_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_problems');
    }
};
