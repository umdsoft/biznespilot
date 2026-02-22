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
        Schema::table('video_content_requests', function (Blueprint $table) {
            $table->text('thumbnail_url')->nullable()->change();
            $table->text('video_title')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('video_content_requests', function (Blueprint $table) {
            $table->string('thumbnail_url')->nullable()->change();
            $table->string('video_title')->nullable()->change();
        });
    }
};
