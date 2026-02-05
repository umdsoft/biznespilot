<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IntegrationGuardService uchun qidiruv indekslari.
     * instagram_id va bot_username bo'yicha global tekshiruvni tezlashtiradi.
     */
    public function up(): void
    {
        if (!$this->indexExists('instagram_accounts', 'instagram_accounts_instagram_id_index')) {
            Schema::table('instagram_accounts', function (Blueprint $table) {
                $table->index('instagram_id');
            });
        }

        if (!$this->indexExists('telegram_bots', 'telegram_bots_bot_username_index')) {
            Schema::table('telegram_bots', function (Blueprint $table) {
                $table->index('bot_username');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('instagram_accounts', 'instagram_accounts_instagram_id_index')) {
            Schema::table('instagram_accounts', function (Blueprint $table) {
                $table->dropIndex(['instagram_id']);
            });
        }

        if ($this->indexExists('telegram_bots', 'telegram_bots_bot_username_index')) {
            Schema::table('telegram_bots', function (Blueprint $table) {
                $table->dropIndex(['bot_username']);
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }
};
