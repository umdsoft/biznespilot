<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance composite indexes — Quick Win pack from DB performance audit
 * (storage/logs/db-perf-audit.md).
 *
 * Covers: Inbox (Telegram + Chatbot), notifications bell, agent message replay,
 * feedback queue, store orders dashboard, store product catalog, per-lead call
 * history. All are new ADD INDEX operations — no drops, no data migration, safe
 * to run online.
 *
 * Idempotent: each index add is guarded via indexExists() so re-running on a
 * partially-migrated environment is safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. chatbot_conversations — Unified Inbox (chatbot) hot query
        $this->addIndex('chatbot_conversations',
            ['business_id', 'status', 'last_message_at'],
            'idx_cb_biz_status_last_msg'
        );

        // 2. telegram_conversations — Unified Inbox (telegram)
        $this->addIndex('telegram_conversations',
            ['business_id', 'status', 'last_message_at'],
            'idx_tg_biz_status_last_msg'
        );

        // 3. in_app_notifications — bell dropdown (every authenticated page)
        $this->addIndex('in_app_notifications',
            ['user_id', 'is_read', 'created_at'],
            'idx_notif_user_read_created'
        );

        // 4. agent_messages — conversation replay (O(k) instead of O(n))
        $this->addIndex('agent_messages',
            ['conversation_id', 'created_at'],
            'idx_agent_msg_conv_created'
        );

        // 5. feedback_reports — admin feedback queue
        $this->addIndex('feedback_reports',
            ['business_id', 'status', 'created_at'],
            'idx_feedback_biz_status_created'
        );

        // 6. call_logs — per-lead recent calls + marketing direction filter
        $this->addIndex('call_logs',
            ['lead_id', 'created_at'],
            'idx_call_logs_lead_created'
        );
        $this->addIndex('call_logs',
            ['business_id', 'direction', 'created_at'],
            'idx_call_logs_biz_dir_created'
        );

        // 7. store_orders — dashboard revenue chart + store status view
        $this->addIndex('store_orders',
            ['store_id', 'created_at', 'status'],
            'idx_store_orders_store_created_status'
        );

        // 8. store_products — MiniApp catalog listing
        $this->addIndex('store_products',
            ['store_id', 'is_active', 'sort_order'],
            'idx_store_products_active_sort'
        );
        $this->addIndex('store_products',
            ['store_id', 'category_id', 'is_active'],
            'idx_store_products_cat_active'
        );

        // 9. store_product_images — primaryImage hasOne lookup
        $this->addIndex('store_product_images',
            ['product_id', 'is_primary'],
            'idx_product_images_primary'
        );

        // 10. chat_messages — user chat history
        $this->addIndex('chat_messages',
            ['business_id', 'user_id', 'created_at'],
            'idx_chat_msg_biz_user_created'
        );
    }

    public function down(): void
    {
        $this->dropIndex('chatbot_conversations', 'idx_cb_biz_status_last_msg');
        $this->dropIndex('telegram_conversations', 'idx_tg_biz_status_last_msg');
        $this->dropIndex('in_app_notifications', 'idx_notif_user_read_created');
        $this->dropIndex('agent_messages', 'idx_agent_msg_conv_created');
        $this->dropIndex('feedback_reports', 'idx_feedback_biz_status_created');
        $this->dropIndex('call_logs', 'idx_call_logs_lead_created');
        $this->dropIndex('call_logs', 'idx_call_logs_biz_dir_created');
        $this->dropIndex('store_orders', 'idx_store_orders_store_created_status');
        $this->dropIndex('store_products', 'idx_store_products_active_sort');
        $this->dropIndex('store_products', 'idx_store_products_cat_active');
        $this->dropIndex('store_product_images', 'idx_product_images_primary');
        $this->dropIndex('chat_messages', 'idx_chat_msg_biz_user_created');
    }

    protected function addIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        // Check all columns exist — silently skip if schema diverges
        foreach ($columns as $col) {
            if (! Schema::hasColumn($table, $col)) {
                return;
            }
        }

        if ($this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($columns, $name) {
            $t->index($columns, $name);
        });
    }

    protected function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($name) {
            $t->dropIndex($name);
        });
    }

    protected function indexExists(string $table, string $name): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = Schema::getConnection()->select(
                "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?",
                [$table, $name]
            );
            return count($rows) > 0;
        }

        $rows = Schema::getConnection()->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $name]
        );
        return count($rows) > 0;
    }
};
