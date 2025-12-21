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
        Schema::create('chatbot_knowledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->text('question');
            $table->text('answer');
            $table->string('category')->nullable();
            $table->integer('priority')->default(0);
            $table->integer('usage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('keywords')->nullable(); // Keywords for better matching
            $table->json('related_questions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_knowledge');
    }
};
