<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_post_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_post_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('business_id')->constrained()->onDelete('cascade');
            $table->string('platform', 50);
            $table->string('external_id')->nullable();
            $table->string('external_url')->nullable();
            // Statistika
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->unsignedInteger('saves')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('forwards')->default(0);
            $table->decimal('engagement_rate', 8, 4)->default(0);
            // Sinxronizatsiya
            $table->timestamp('synced_at')->nullable();
            $table->string('sync_status', 20)->default('pending');
            $table->timestamps();
            // Indekslar
            $table->unique(['content_post_id', 'platform']);
            $table->index(['business_id', 'platform']);
            $table->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_post_links');
    }
};
