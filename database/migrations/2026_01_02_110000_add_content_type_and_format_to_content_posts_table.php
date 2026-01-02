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
        Schema::table('content_posts', function (Blueprint $table) {
            // Content type: educational, entertaining, inspirational, promotional, behind_scenes, ugc
            $table->string('content_type', 50)->nullable()->after('type');
            // Format: short_video, long_video, carousel, single_image, story, text_post, live, poll
            $table->string('format', 50)->nullable()->after('content_type');

            // Indexes for filtering
            $table->index('content_type');
            $table->index('format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_posts', function (Blueprint $table) {
            $table->dropIndex(['content_type']);
            $table->dropIndex(['format']);
            $table->dropColumn(['content_type', 'format']);
        });
    }
};
