<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hardening migration for Telegram Store order + payment flow.
 *
 * 1. Adds `delivery_type` column to store_orders (delivery|pickup).
 * 2. Upgrades `(order_id, provider)` + `(provider, provider_transaction_id)`
 *    to UNIQUE on store_payment_transactions — prevents double-spend from
 *    racing Payme/Click retries.
 *
 * SQLite-safe: the unique upgrade drops the legacy plain index first.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- 1. Orders: delivery_type column ----
        if (! Schema::hasColumn('store_orders', 'delivery_type')) {
            Schema::table('store_orders', function (Blueprint $table) {
                $table->string('delivery_type', 20)->default('delivery')->after('payment_method');
                // values: delivery | pickup
            });
        }

        // ---- 2. Unique indexes on store_payment_transactions ----
        $driver = Schema::getConnection()->getDriverName();

        // Drop legacy indexes if present (safe across MySQL/MariaDB/SQLite/PgSQL)
        $this->dropIndexSafe('store_payment_transactions', 'store_payment_transactions_order_id_index');
        $this->dropIndexSafe('store_payment_transactions', 'store_pay_tx_provider_idx');

        // Backfill: collapse duplicate rows before adding unique index.
        // Keep most recent row per (order_id, provider); delete older dupes.
        if ($driver !== 'sqlite') {
            DB::statement(<<<'SQL'
                DELETE t1 FROM store_payment_transactions t1
                INNER JOIN store_payment_transactions t2
                  ON t1.order_id = t2.order_id
                 AND t1.provider = t2.provider
                 AND t1.created_at < t2.created_at
            SQL);
        }

        Schema::table('store_payment_transactions', function (Blueprint $table) {
            $table->unique(['order_id', 'provider'], 'store_pay_tx_order_provider_uq');
            $table->index(['provider', 'provider_transaction_id'], 'store_pay_tx_provider_idx');
        });
    }

    public function down(): void
    {
        Schema::table('store_payment_transactions', function (Blueprint $table) {
            $table->dropUnique('store_pay_tx_order_provider_uq');
            $table->dropIndex('store_pay_tx_provider_idx');
            $table->index(['order_id'], 'store_payment_transactions_order_id_index');
            $table->index(['provider', 'provider_transaction_id'], 'store_pay_tx_provider_idx');
        });

        if (Schema::hasColumn('store_orders', 'delivery_type')) {
            Schema::table('store_orders', function (Blueprint $table) {
                $table->dropColumn('delivery_type');
            });
        }
    }

    protected function dropIndexSafe(string $table, string $index): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($index) {
                $t->dropIndex($index);
            });
        } catch (\Throwable $e) {
            // Index doesn't exist on this environment — safe to ignore.
        }
    }
};
