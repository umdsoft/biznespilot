<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     * Performance indexes for frequently queried columns
     */
    public function up(): void
    {
        // Leads table indexes
        Schema::table('leads', function (Blueprint $table) {
            // Index for assigned_to queries (operator performance)
            if (!$this->indexExists('leads', 'leads_assigned_to_index')) {
                $table->index('assigned_to', 'leads_assigned_to_index');
            }

            // Composite index for status + business_id (funnel queries)
            if (!$this->indexExists('leads', 'leads_business_status_index')) {
                $table->index(['business_id', 'status'], 'leads_business_status_index');
            }

            // Composite index for date range queries
            if (!$this->indexExists('leads', 'leads_business_created_index')) {
                $table->index(['business_id', 'created_at'], 'leads_business_created_index');
            }
            if (!$this->indexExists('leads', 'leads_business_updated_index')) {
                $table->index(['business_id', 'updated_at'], 'leads_business_updated_index');
            }

            // Index for source_id (marketing channel analysis)
            if (!$this->indexExists('leads', 'leads_source_id_index')) {
                $table->index('source_id', 'leads_source_id_index');
            }

            // Index for estimated_value (revenue calculations)
            if (!$this->indexExists('leads', 'leads_estimated_value_index')) {
                $table->index('estimated_value', 'leads_estimated_value_index');
            }
        });

        // Customers table indexes
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!$this->indexExists('customers', 'customers_dream_buyer_id_index')) {
                    $table->index('dream_buyer_id', 'customers_dream_buyer_id_index');
                }
                if (!$this->indexExists('customers', 'customers_last_purchase_index')) {
                    $table->index('last_purchase_at', 'customers_last_purchase_index');
                }
                if (!$this->indexExists('customers', 'customers_business_dream_buyer_index')) {
                    $table->index(['business_id', 'dream_buyer_id'], 'customers_business_dream_buyer_index');
                }
            });
        }

        // Sales table indexes
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!$this->indexExists('sales', 'sales_marketing_channel_index')) {
                    $table->index('marketing_channel_id', 'sales_marketing_channel_index');
                }
                if (!$this->indexExists('sales', 'sales_business_customer_index')) {
                    $table->index(['business_id', 'customer_id'], 'sales_business_customer_index');
                }
                if (!$this->indexExists('sales', 'sales_business_created_index')) {
                    $table->index(['business_id', 'created_at'], 'sales_business_created_index');
                }
            });
        }

        // Campaigns table indexes
        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                if (!$this->indexExists('campaigns', 'campaigns_business_status_index') && Schema::hasColumn('campaigns', 'business_id') && Schema::hasColumn('campaigns', 'status')) {
                    $table->index(['business_id', 'status'], 'campaigns_business_status_index');
                }
                if (!$this->indexExists('campaigns', 'campaigns_date_range_index') && Schema::hasColumn('campaigns', 'start_date') && Schema::hasColumn('campaigns', 'end_date')) {
                    $table->index(['start_date', 'end_date'], 'campaigns_date_range_index');
                }
            });
        }

        // Tasks table indexes
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (!$this->indexExists('tasks', 'tasks_business_assigned_status_index')) {
                    $table->index(['business_id', 'assigned_to', 'status'], 'tasks_business_assigned_status_index');
                }
                if (!$this->indexExists('tasks', 'tasks_due_date_index')) {
                    $table->index('due_date', 'tasks_due_date_index');
                }
            });
        }

        // Business users table indexes
        if (Schema::hasTable('business_users')) {
            Schema::table('business_users', function (Blueprint $table) {
                if (!$this->indexExists('business_users', 'business_users_business_dept_index')) {
                    $table->index(['business_id', 'department'], 'business_users_business_dept_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_assigned_to_index');
            $table->dropIndex('leads_business_status_index');
            $table->dropIndex('leads_business_created_index');
            $table->dropIndex('leads_business_updated_index');
            $table->dropIndex('leads_source_id_index');
            $table->dropIndex('leads_estimated_value_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_dream_buyer_id_index');
            $table->dropIndex('customers_last_purchase_index');
            $table->dropIndex('customers_business_dream_buyer_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_marketing_channel_index');
            $table->dropIndex('sales_business_customer_index');
            $table->dropIndex('sales_business_created_index');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_business_status_index');
            $table->dropIndex('campaigns_date_range_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_business_assigned_status_index');
            $table->dropIndex('tasks_due_date_index');
        });

        Schema::table('business_users', function (Blueprint $table) {
            $table->dropIndex('business_users_business_dept_index');
        });
    }
};
