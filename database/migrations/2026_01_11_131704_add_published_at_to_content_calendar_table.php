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
        Schema::table('content_calendar', function (Blueprint $table) {
            if (! Schema::hasColumn('content_calendar', 'published_at')) {
                $table->timestamp('published_at')->nullable();
            }
            if (! Schema::hasColumn('content_calendar', 'external_post_id')) {
                $table->string('external_post_id')->nullable();
            }
            if (! Schema::hasColumn('content_calendar', 'post_url')) {
                $table->string('post_url')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_calendar', function (Blueprint $table) {
            $columns = ['published_at', 'external_post_id', 'post_url'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('content_calendar', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
