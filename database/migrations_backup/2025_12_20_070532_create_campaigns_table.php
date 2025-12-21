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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['broadcast', 'drip', 'trigger'])->default('broadcast');
            $table->enum('channel', ['whatsapp', 'instagram', 'telegram', 'facebook', 'all'])->default('whatsapp');
            $table->text('message_template');
            $table->json('target_audience')->nullable();
            $table->enum('schedule_type', ['immediate', 'scheduled', 'recurring'])->default('immediate');
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'running', 'completed', 'paused', 'failed'])->default('draft');
            $table->json('settings')->nullable();
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
