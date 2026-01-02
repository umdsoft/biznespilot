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
            // Drop the index first
            $table->dropIndex(['platform']);
        });

        Schema::table('content_posts', function (Blueprint $table) {
            // Change platform column to TEXT to support JSON array of multiple platforms
            $table->text('platform')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_posts', function (Blueprint $table) {
            $table->string('platform', 50)->nullable()->change();
            $table->index('platform');
        });
    }
};
