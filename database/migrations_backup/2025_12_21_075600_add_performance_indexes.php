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
        // Add composite index for sales queries on dashboard
        $this->safeAddIndex('sales', ['business_id', 'created_at'], 'sales_business_created_idx');

        // Add index for instagram automations status queries
        $this->safeAddIndex('instagram_automations', ['instagram_account_id', 'status'], 'automations_account_status_idx');
        $this->safeAddIndex('instagram_automations', ['status', 'type'], 'automations_status_type_idx');

        // Add index for dream buyers
        if (Schema::hasTable('dream_buyers')) {
            $this->safeAddIndex('dream_buyers', ['business_id'], 'dream_buyers_business_id_index');
        }

        // Add index for marketing channels
        if (Schema::hasTable('marketing_channels')) {
            $this->safeAddIndex('marketing_channels', ['business_id', 'is_active'], 'channels_business_active_idx');
        }

        // Add index for offers
        if (Schema::hasTable('offers')) {
            $this->safeAddIndex('offers', ['business_id', 'status'], 'offers_business_status_idx');
        }

        // Add index for conversations participant lookup
        $this->safeAddIndex('instagram_conversations', ['instagram_account_id', 'status', 'last_message_at'], 'conv_account_status_message_idx');

        // Add index for messages conversation lookup
        $this->safeAddIndex('instagram_messages', ['conversation_id', 'created_at'], 'messages_conv_created_idx');

        // Add index for flow nodes
        if (Schema::hasTable('instagram_flow_nodes')) {
            $this->safeAddIndex('instagram_flow_nodes', ['automation_id', 'node_type'], 'flow_nodes_automation_type_idx');
        }

        // Add index for flow edges
        if (Schema::hasTable('instagram_flow_edges')) {
            $this->safeAddIndex('instagram_flow_edges', ['automation_id'], 'flow_edges_automation_idx');
        }
    }

    /**
     * Safely add an index - skip if already exists
     */
    private function safeAddIndex(string $table, array $columns, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->safeDropIndex('sales', 'sales_business_created_idx');
        $this->safeDropIndex('instagram_automations', 'automations_account_status_idx');
        $this->safeDropIndex('instagram_automations', 'automations_status_type_idx');

        if (Schema::hasTable('dream_buyers')) {
            $this->safeDropIndex('dream_buyers', 'dream_buyers_business_id_index');
        }

        if (Schema::hasTable('marketing_channels')) {
            $this->safeDropIndex('marketing_channels', 'channels_business_active_idx');
        }

        if (Schema::hasTable('offers')) {
            $this->safeDropIndex('offers', 'offers_business_status_idx');
        }

        $this->safeDropIndex('instagram_conversations', 'conv_account_status_message_idx');
        $this->safeDropIndex('instagram_messages', 'messages_conv_created_idx');

        if (Schema::hasTable('instagram_flow_nodes')) {
            $this->safeDropIndex('instagram_flow_nodes', 'flow_nodes_automation_type_idx');
        }

        if (Schema::hasTable('instagram_flow_edges')) {
            $this->safeDropIndex('instagram_flow_edges', 'flow_edges_automation_idx');
        }
    }

    /**
     * Safely drop an index - skip if doesn't exist
     */
    private function safeDropIndex(string $tableName, string $indexName): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }
    }
};
