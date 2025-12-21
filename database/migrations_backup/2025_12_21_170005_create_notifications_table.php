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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Notification details
            $table->string('type'); // alert, insight, report, system, celebration
            $table->string('channel')->default('in_app'); // in_app, email, telegram, sms
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('priority')->default('medium'); // critical, high, medium, low

            // Status
            $table->timestamp('read_at')->nullable();
            $table->timestamp('clicked_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'read_at']);
            $table->index(['user_id', 'read_at']);
            $table->index(['type', 'channel']);
            $table->index(['related_type', 'related_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
