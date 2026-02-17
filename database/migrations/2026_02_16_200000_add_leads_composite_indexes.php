<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (! $this->indexExists('leads', 'leads_biz_assigned_status_idx')) {
                    $table->index(['business_id', 'assigned_to', 'status'], 'leads_biz_assigned_status_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropIndex('leads_biz_assigned_status_idx');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = config('database.connections.mysql.database');

        $result = DB::select('
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = ?
            AND table_name = ?
            AND index_name = ?
        ', [$database, $table, $indexName]);

        return $result[0]->count > 0;
    }
};
