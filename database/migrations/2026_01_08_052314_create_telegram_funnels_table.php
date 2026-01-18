<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_funnels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('telegram_bot_id')->constrained()->cascadeOnDelete();

            // Identification
            $table->string('name');
            $table->string('slug');

            // Type
            $table->enum('type', [
                'welcome',
                'product',
                'order',
                'pricing',
                'support',
                'feedback',
                'payment',
                'broadcast',
                'custom',
            ])->default('custom');

            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);

            // Meta
            $table->text('description')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['telegram_bot_id', 'slug']);
            $table->index(['business_id', 'is_active']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_funnels');
    }
};
