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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Alert recipient
            $table->enum('type', ['goal', 'budget', 'performance', 'system', 'integration', 'payment', 'subscription', 'custom'])->default('custom');
            $table->string('title');
            $table->text('message');
            $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('info');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->string('action_url')->nullable();
            $table->string('action_label')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('triggered_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_read']);
            $table->index(['user_id', 'is_read']);
            $table->index(['severity', 'is_read']);
            $table->index('triggered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
