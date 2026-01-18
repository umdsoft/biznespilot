<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_user_states', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('telegram_user_id')->unique()->constrained()->cascadeOnDelete();

            // Current position
            $table->foreignUuid('current_funnel_id')->nullable()->constrained('telegram_funnels')->nullOnDelete();
            $table->foreignUuid('current_step_id')->nullable()->constrained('telegram_funnel_steps')->nullOnDelete();

            // Collected data
            $table->json('collected_data')->nullable();

            // What are we waiting for?
            $table->enum('waiting_for', [
                'none',
                'callback',
                'text',
                'phone',
                'email',
                'number',
                'photo',
                'location',
                'any',
            ])->default('none');

            // Last bot message
            $table->bigInteger('last_message_id')->nullable();
            $table->bigInteger('last_message_chat_id')->nullable();

            // Context
            $table->json('context')->nullable();

            // Expiration
            $table->timestamp('expires_at')->nullable();

            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Indexes
            $table->index('current_funnel_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_user_states');
    }
};
