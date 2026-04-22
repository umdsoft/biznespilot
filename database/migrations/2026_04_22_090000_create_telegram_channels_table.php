<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('connected_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Telegram identifiers (chat.id is -100XXXXXXXX for channels; use BIGINT)
            $table->bigInteger('telegram_chat_id')->unique();
            $table->string('chat_username')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('invite_link')->nullable();

            // channel | supergroup
            $table->string('type', 20)->default('channel');

            // Current snapshot
            $table->integer('subscriber_count')->default(0);
            $table->integer('admin_count')->default(0);

            // Admin status from bot's perspective
            // administrator | kicked | left | restricted | member
            $table->string('admin_status', 20)->default('administrator');
            $table->json('admin_rights')->nullable();

            // Lifecycle
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['business_id', 'is_active']);
            $table->index('admin_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_channels');
    }
};
