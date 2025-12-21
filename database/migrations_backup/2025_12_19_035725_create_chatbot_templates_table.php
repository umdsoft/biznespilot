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
        Schema::create('chatbot_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('trigger'); // Keywords or patterns that trigger this template
            $table->text('response');
            $table->enum('trigger_type', ['exact', 'contains', 'starts_with', 'regex'])->default('contains');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable(); // Additional conditions for triggering
            $table->json('buttons')->nullable(); // Quick reply buttons
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_templates');
    }
};
