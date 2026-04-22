<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kanban pipeline uchun kompozit indeks.
 *
 * Query pattern: `WHERE business_id = ? AND status = ? ORDER BY created_at DESC`
 * Mavjud indekslar business_id + updated_at bo'yicha bor, lekin created_at
 * uchun yo'q. Kanban columnlari created_at DESC tartiblanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (!$this->indexExists('leads', 'leads_business_status_created_idx')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->index(
                    ['business_id', 'status', 'created_at'],
                    'leads_business_status_created_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if ($this->indexExists('leads', 'leads_business_status_created_idx')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropIndex('leads_business_status_created_idx');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );
        return count($result) > 0;
    }
};
