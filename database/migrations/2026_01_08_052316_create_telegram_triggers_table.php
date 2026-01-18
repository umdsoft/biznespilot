<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_triggers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('telegram_bot_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('funnel_id')->constrained('telegram_funnels')->cascadeOnDelete();
            $table->foreignUuid('step_id')->nullable()->constrained('telegram_funnel_steps')->nullOnDelete();

            // Trigger type
            $table->enum('type', [
                'command',
                'callback',
                'keyword',
                'event',
            ]);

            // Trigger value
            $table->string('value');

            // Matching type
            $table->enum('match_type', [
                'exact',
                'contains',
                'starts_with',
                'ends_with',
                'regex',
                'wildcard',
            ])->default('exact');

            // Priority
            $table->integer('priority')->default(0);

            // Status
            $table->boolean('is_active')->default(true);

            // Additional conditions
            $table->json('conditions')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['telegram_bot_id', 'type', 'is_active']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_triggers');
    }
};
