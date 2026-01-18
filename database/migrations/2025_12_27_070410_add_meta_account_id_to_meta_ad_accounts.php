<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meta_ad_accounts', function (Blueprint $table) {
            // Add meta_account_id column if not exists
            if (! Schema::hasColumn('meta_ad_accounts', 'meta_account_id')) {
                $table->string('meta_account_id')->nullable()->after('integration_id');
            }

            // Add is_primary column if not exists
            if (! Schema::hasColumn('meta_ad_accounts', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('timezone');
            }

            // Add amount_spent column if not exists
            if (! Schema::hasColumn('meta_ad_accounts', 'amount_spent')) {
                $table->decimal('amount_spent', 14, 2)->default(0)->after('is_primary');
            }

            // Add account_status column if not exists
            if (! Schema::hasColumn('meta_ad_accounts', 'account_status')) {
                $table->integer('account_status')->default(1)->after('amount_spent');
            }

            // Add metadata column if not exists
            if (! Schema::hasColumn('meta_ad_accounts', 'metadata')) {
                $table->json('metadata')->nullable()->after('account_status');
            }

            // Add last_sync_at column if not exists
            if (! Schema::hasColumn('meta_ad_accounts', 'last_sync_at')) {
                $table->timestamp('last_sync_at')->nullable()->after('metadata');
            }
        });

        // Copy data from account_id to meta_account_id if account_id has data
        DB::statement('UPDATE meta_ad_accounts SET meta_account_id = account_id WHERE meta_account_id IS NULL AND account_id IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_ad_accounts', function (Blueprint $table) {
            $columns = ['meta_account_id', 'is_primary', 'amount_spent', 'account_status', 'metadata', 'last_sync_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('meta_ad_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
