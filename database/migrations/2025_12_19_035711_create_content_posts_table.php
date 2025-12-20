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
        Schema::create('content_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained('marketing_channels')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Creator
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['post', 'article', 'video', 'image', 'story', 'reel', 'other'])->default('post');
            $table->enum('status', ['draft', 'scheduled', 'published', 'failed', 'archived'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('external_id')->nullable(); // ID from social media platform
            $table->string('external_url')->nullable();
            $table->json('media')->nullable(); // images, videos, etc.
            $table->json('metrics')->nullable(); // likes, shares, comments, views, etc.
            $table->text('ai_suggestions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
            $table->index('scheduled_at');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_posts');
    }
};
