<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('telegram_conversations', 'business_connection_id')) {
                $table->string('business_connection_id')->nullable()->after('telegram_bot_id');
                $table->string('mode')->default('bot')->after('business_connection_id'); // bot | business
                $table->index(['business_connection_id', 'mode']);
            }
        });

        Schema::table('telegram_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('telegram_messages', 'business_connection_id')) {
                $table->string('business_connection_id')->nullable()->after('telegram_chat_id');
                $table->index('business_connection_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_conversations', function (Blueprint $table) {
            if (Schema::hasColumn('telegram_conversations', 'business_connection_id')) {
                $table->dropIndex(['business_connection_id', 'mode']);
                $table->dropColumn(['business_connection_id', 'mode']);
            }
        });

        Schema::table('telegram_messages', function (Blueprint $table) {
            if (Schema::hasColumn('telegram_messages', 'business_connection_id')) {
                $table->dropIndex(['business_connection_id']);
                $table->dropColumn('business_connection_id');
            }
        });
    }
};
