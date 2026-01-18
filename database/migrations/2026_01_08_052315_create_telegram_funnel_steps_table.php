<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_funnel_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('funnel_id')->constrained('telegram_funnels')->cascadeOnDelete();

            // Identification
            $table->string('name');
            $table->string('slug');
            $table->integer('order')->default(0);

            // Content
            $table->json('content');

            // Keyboard
            $table->json('keyboard')->nullable();

            // Input expectation
            $table->enum('input_type', [
                'none',
                'text',
                'phone',
                'email',
                'number',
                'photo',
                'location',
                'any',
            ])->default('none');

            // Validation
            $table->json('validation')->nullable();

            // Actions
            $table->json('actions')->nullable();

            // Transitions
            $table->json('transitions')->nullable();

            // Options
            $table->boolean('edit_previous_message')->default(false);
            $table->boolean('delete_user_message')->default(false);
            $table->integer('delay_ms')->default(0);

            $table->timestamps();

            // Indexes
            $table->unique(['funnel_id', 'slug']);
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_funnel_steps');
    }
};
