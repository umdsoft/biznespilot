<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. telegram_stores — store_type + enabled_features
        Schema::table('telegram_stores', function (Blueprint $table) {
            $table->string('store_type', 30)->default('ecommerce')->after('telegram_bot_id');
            $table->json('enabled_features')->nullable()->after('settings');
            $table->index(['business_id', 'store_type']);
        });

        // 2. store_order_items — polimorfik item_type/item_id
        Schema::table('store_order_items', function (Blueprint $table) {
            $table->string('item_type', 100)->nullable()->after('variant_id');
            $table->uuid('item_id')->nullable()->after('item_type');
            $table->json('item_metadata')->nullable()->after('total');
            $table->index(['item_type', 'item_id']);
        });

        // 3. store_cart_items — polimorfik item_type/item_id
        Schema::table('store_cart_items', function (Blueprint $table) {
            $table->string('item_type', 100)->nullable()->after('variant_id');
            $table->uuid('item_id')->nullable()->after('item_type');
            $table->json('selections')->nullable()->after('price');
            $table->index(['item_type', 'item_id']);
        });

        // 4. store_reviews — polimorfik reviewable_type/reviewable_id
        Schema::table('store_reviews', function (Blueprint $table) {
            $table->string('reviewable_type', 100)->nullable()->after('store_id');
            $table->uuid('reviewable_id')->nullable()->after('reviewable_type');
            $table->json('aspects')->nullable()->after('comment');
            $table->index(['reviewable_type', 'reviewable_id']);
        });

        // 5. Backfill — mavjud ecommerce datani polimorfik ustunlarga ko'chirish
        DB::table('store_order_items')
            ->whereNotNull('product_id')
            ->whereNull('item_type')
            ->update([
                'item_type' => 'App\\Models\\Store\\StoreProduct',
                'item_id' => DB::raw('product_id'),
            ]);

        DB::table('store_cart_items')
            ->whereNotNull('product_id')
            ->whereNull('item_type')
            ->update([
                'item_type' => 'App\\Models\\Store\\StoreProduct',
                'item_id' => DB::raw('product_id'),
            ]);

        DB::table('store_reviews')
            ->whereNotNull('product_id')
            ->whereNull('reviewable_type')
            ->update([
                'reviewable_type' => 'App\\Models\\Store\\StoreProduct',
                'reviewable_id' => DB::raw('product_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('store_reviews', function (Blueprint $table) {
            $table->dropIndex(['reviewable_type', 'reviewable_id']);
            $table->dropColumn(['reviewable_type', 'reviewable_id', 'aspects']);
        });

        Schema::table('store_cart_items', function (Blueprint $table) {
            $table->dropIndex(['item_type', 'item_id']);
            $table->dropColumn(['item_type', 'item_id', 'selections']);
        });

        Schema::table('store_order_items', function (Blueprint $table) {
            $table->dropIndex(['item_type', 'item_id']);
            $table->dropColumn(['item_type', 'item_id', 'item_metadata']);
        });

        Schema::table('telegram_stores', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'store_type']);
            $table->dropColumn(['store_type', 'enabled_features']);
        });
    }
};
