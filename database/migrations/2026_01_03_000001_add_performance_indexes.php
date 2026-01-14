<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PERFORMANCE OPTIMIZATION: Adding critical indexes for high-volume tables
     * Expected improvement: 2-10x faster queries
     */
    public function up(): void
    {
        // Skip for SQLite (used in tests) - information_schema not supported
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        // Leads table indexes
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                // For filtering by source
                if (!$this->indexExists('leads', 'leads_business_id_source_index')) {
                    $table->index(['business_id', 'source'], 'leads_business_id_source_index');
                }
                // For date range queries
                if (!$this->indexExists('leads', 'leads_created_at_index')) {
                    $table->index('created_at', 'leads_created_at_index');
                }
                // For assigned user filtering
                if (!$this->indexExists('leads', 'leads_assigned_to_index')) {
                    $table->index('assigned_to', 'leads_assigned_to_index');
                }
            });
        }

        // AI Insights table indexes
        if (Schema::hasTable('ai_insights')) {
            Schema::table('ai_insights', function (Blueprint $table) {
                // For dashboard queries (unread insights)
                if (!$this->indexExists('ai_insights', 'ai_insights_business_is_read_created_index')) {
                    $table->index(['business_id', 'is_read', 'created_at'], 'ai_insights_business_is_read_created_index');
                }
                // For status filtering
                if (!$this->indexExists('ai_insights', 'ai_insights_business_status_index')) {
                    $table->index(['business_id', 'status'], 'ai_insights_business_status_index');
                }
                // For priority sorting
                if (!$this->indexExists('ai_insights', 'ai_insights_business_priority_index')) {
                    $table->index(['business_id', 'priority'], 'ai_insights_business_priority_index');
                }
            });
        }

        // Activity logs table indexes
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                // For action filtering
                if (!$this->indexExists('activity_logs', 'activity_logs_action_index')) {
                    $table->index('action', 'activity_logs_action_index');
                }
                // For user activity queries
                if (!$this->indexExists('activity_logs', 'activity_logs_user_created_index')) {
                    $table->index(['user_id', 'created_at'], 'activity_logs_user_created_index');
                }
                // For business activity queries
                if (!$this->indexExists('activity_logs', 'activity_logs_business_created_index')) {
                    $table->index(['business_id', 'created_at'], 'activity_logs_business_created_index');
                }
            });
        }

        // Customers table indexes
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                // For status filtering
                if (!$this->indexExists('customers', 'customers_business_status_index')) {
                    $table->index(['business_id', 'status'], 'customers_business_status_index');
                }
                // For duplicate detection
                if (!$this->indexExists('customers', 'customers_email_index')) {
                    $table->index('email', 'customers_email_index');
                }
                if (!$this->indexExists('customers', 'customers_phone_index')) {
                    $table->index('phone', 'customers_phone_index');
                }
            });
        }

        // Marketing channels table indexes
        if (Schema::hasTable('marketing_channels')) {
            Schema::table('marketing_channels', function (Blueprint $table) {
                // For active channels query
                if (!$this->indexExists('marketing_channels', 'marketing_channels_business_active_index')) {
                    $table->index(['business_id', 'is_active', 'created_at'], 'marketing_channels_business_active_index');
                }
            });
        }

        // Chatbot messages table indexes
        if (Schema::hasTable('chatbot_messages')) {
            Schema::table('chatbot_messages', function (Blueprint $table) {
                // For conversation history
                if (!$this->indexExists('chatbot_messages', 'chatbot_messages_conversation_created_index')) {
                    $table->index(['conversation_id', 'created_at'], 'chatbot_messages_conversation_created_index');
                }
            });
        }

        // Content posts table indexes
        if (Schema::hasTable('content_posts')) {
            Schema::table('content_posts', function (Blueprint $table) {
                // For calendar view
                if (!$this->indexExists('content_posts', 'content_posts_business_scheduled_index')) {
                    $table->index(['business_id', 'scheduled_at'], 'content_posts_business_scheduled_index');
                }
                // For status filtering
                if (!$this->indexExists('content_posts', 'content_posts_business_status_index')) {
                    $table->index(['business_id', 'status'], 'content_posts_business_status_index');
                }
            });
        }

        // Competitors table indexes
        if (Schema::hasTable('competitors')) {
            Schema::table('competitors', function (Blueprint $table) {
                // For business competitors listing
                if (!$this->indexExists('competitors', 'competitors_business_created_index')) {
                    $table->index(['business_id', 'created_at'], 'competitors_business_created_index');
                }
            });
        }

        // Orders table indexes
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // For date range revenue queries
                if (!$this->indexExists('orders', 'orders_business_created_index')) {
                    $table->index(['business_id', 'created_at'], 'orders_business_created_index');
                }
                // For customer orders
                if (!$this->indexExists('orders', 'orders_customer_created_index')) {
                    $table->index(['customer_id', 'created_at'], 'orders_customer_created_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip for SQLite
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Drop leads indexes
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropIndex('leads_business_id_source_index');
                $table->dropIndex('leads_created_at_index');
                $table->dropIndex('leads_assigned_to_index');
            });
        }

        // Drop ai_insights indexes
        if (Schema::hasTable('ai_insights')) {
            Schema::table('ai_insights', function (Blueprint $table) {
                $table->dropIndex('ai_insights_business_is_read_created_index');
                $table->dropIndex('ai_insights_business_status_index');
                $table->dropIndex('ai_insights_business_priority_index');
            });
        }

        // Drop activity_logs indexes
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropIndex('activity_logs_action_index');
                $table->dropIndex('activity_logs_user_created_index');
                $table->dropIndex('activity_logs_business_created_index');
            });
        }

        // Drop customers indexes
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex('customers_business_status_index');
                $table->dropIndex('customers_email_index');
                $table->dropIndex('customers_phone_index');
            });
        }

        // Drop marketing_channels indexes
        if (Schema::hasTable('marketing_channels')) {
            Schema::table('marketing_channels', function (Blueprint $table) {
                $table->dropIndex('marketing_channels_business_active_index');
            });
        }

        // Drop chatbot_messages indexes
        if (Schema::hasTable('chatbot_messages')) {
            Schema::table('chatbot_messages', function (Blueprint $table) {
                $table->dropIndex('chatbot_messages_conversation_created_index');
            });
        }

        // Drop content_posts indexes
        if (Schema::hasTable('content_posts')) {
            Schema::table('content_posts', function (Blueprint $table) {
                $table->dropIndex('content_posts_business_scheduled_index');
                $table->dropIndex('content_posts_business_status_index');
            });
        }

        // Drop competitors indexes
        if (Schema::hasTable('competitors')) {
            Schema::table('competitors', function (Blueprint $table) {
                $table->dropIndex('competitors_business_created_index');
            });
        }

        // Drop orders indexes
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_business_created_index');
                $table->dropIndex('orders_customer_created_index');
            });
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $database = config('database.connections.mysql.database');

        $result = \DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = ?
            AND table_name = ?
            AND index_name = ?
        ", [$database, $table, $indexName]);

        return $result[0]->count > 0;
    }
};
