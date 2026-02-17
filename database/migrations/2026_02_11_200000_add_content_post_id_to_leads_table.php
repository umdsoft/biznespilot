<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignUuid('content_post_id')->nullable()->after('campaign_id')
                ->constrained('content_posts')->nullOnDelete();
            $table->index('content_post_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['content_post_id']);
            $table->dropIndex(['content_post_id']);
            $table->dropColumn('content_post_id');
        });
    }
};
