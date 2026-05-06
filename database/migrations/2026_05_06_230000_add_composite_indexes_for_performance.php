<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Performance audit topilgan kompozit indekslar.
 *
 * Hozirgi vaziyat: 100+ business, har birida 10K+ leads/orders.
 * Eng tez-tez chaqiriladigan query'lar full-table scan qiladi:
 *   - DashboardController: Lead::where('business_id',...)->where('status','won')->where('converted_at','>=',...)
 *   - Orders aggregation per business + status
 *   - Customer lookups per business + segment
 *
 * Yangi indekslar:
 *   - leads (business_id, status, converted_at)  — won/converted aggregations
 *   - leads (business_id, status, created_at)    — pipeline filters
 *   - store_orders (business_id, status, created_at) — orders dashboard
 *   - customers (business_id, segment_id)        — segment lookups (agar mavjud bo'lsa)
 *
 * Idempotent: SHOW INDEX orqali tekshiramiz, mavjud bo'lsa skip.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfNotExists('leads', 'leads_biz_status_converted_idx', ['business_id', 'status', 'converted_at']);
        $this->addIndexIfNotExists('leads', 'leads_biz_status_created_idx', ['business_id', 'status', 'created_at']);

        if (Schema::hasTable('store_orders')) {
            $this->addIndexIfNotExists('store_orders', 'store_orders_biz_status_created_idx', ['business_id', 'status', 'created_at']);
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'segment_id')) {
            $this->addIndexIfNotExists('customers', 'customers_biz_segment_idx', ['business_id', 'segment_id']);
        }

        // call_logs hot path
        if (Schema::hasTable('call_logs')) {
            $this->addIndexIfNotExists('call_logs', 'call_logs_biz_created_idx', ['business_id', 'created_at']);
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists('leads', 'leads_biz_status_converted_idx');
        $this->dropIndexIfExists('leads', 'leads_biz_status_created_idx');
        $this->dropIndexIfExists('store_orders', 'store_orders_biz_status_created_idx');
        $this->dropIndexIfExists('customers', 'customers_biz_segment_idx');
        $this->dropIndexIfExists('call_logs', 'call_logs_biz_created_idx');
    }

    private function addIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $existing = collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->unique()
            ->all();

        if (in_array($indexName, $existing, true)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
            $t->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $existing = collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->unique()
            ->all();

        if (! in_array($indexName, $existing, true)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($indexName) {
            $t->dropIndex($indexName);
        });
    }
};
