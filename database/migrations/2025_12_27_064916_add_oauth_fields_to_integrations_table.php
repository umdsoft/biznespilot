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
        Schema::table('integrations', function (Blueprint $table) {
            // Add missing columns for Meta OAuth integration
            if (!Schema::hasColumn('integrations', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('integrations', 'config')) {
                $table->json('config')->nullable()->after('credentials');
            }
            if (!Schema::hasColumn('integrations', 'connected_at')) {
                $table->timestamp('connected_at')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('integrations', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('connected_at');
            }
            if (!Schema::hasColumn('integrations', 'last_error_at')) {
                $table->timestamp('last_error_at')->nullable()->after('last_sync_at');
            }
            if (!Schema::hasColumn('integrations', 'last_error_message')) {
                $table->text('last_error_message')->nullable()->after('last_error_at');
            }
            if (!Schema::hasColumn('integrations', 'sync_count')) {
                $table->integer('sync_count')->default(0)->after('last_error_message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $columns = ['description', 'config', 'connected_at', 'expires_at', 'last_error_at', 'last_error_message', 'sync_count'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('integrations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
