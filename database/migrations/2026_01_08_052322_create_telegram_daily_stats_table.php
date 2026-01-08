<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_daily_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('telegram_bot_id')->constrained()->cascadeOnDelete();

            $table->date('date');

            // User stats
            $table->unsignedInteger('new_users')->default(0);
            $table->unsignedInteger('active_users')->default(0);
            $table->unsignedInteger('blocked_users')->default(0);
            $table->unsignedInteger('unblocked_users')->default(0);

            // Message stats
            $table->unsignedInteger('messages_in')->default(0);
            $table->unsignedInteger('messages_out')->default(0);

            // Conversation stats
            $table->unsignedInteger('conversations_started')->default(0);
            $table->unsignedInteger('conversations_closed')->default(0);
            $table->unsignedInteger('handoffs')->default(0);

            // Lead stats
            $table->unsignedInteger('leads_captured')->default(0);

            // Funnel stats (JSON)
            $table->json('funnel_stats')->nullable();

            // Popular triggers
            $table->json('trigger_stats')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['telegram_bot_id', 'date']);
            $table->index(['business_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_daily_stats');
    }
};
