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
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['marketing', 'sales', 'content', 'product', 'customer', 'competitor', 'general'])->default('general');
            $table->string('title');
            $table->text('content');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->default('neutral');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_actionable')->default(false);
            $table->text('action_taken')->nullable();
            $table->json('data')->nullable(); // Supporting data, metrics, etc.
            $table->timestamp('generated_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_read']);
            $table->index(['type', 'priority']);
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};
