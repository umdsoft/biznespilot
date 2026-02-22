<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. telegram_stores — Asosiy do'kon jadval
        Schema::create('telegram_stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('telegram_bot_id')->nullable()->constrained('telegram_bots')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->string('currency', 10)->default('UZS');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();

            $table->boolean('is_active')->default(false);
            $table->json('settings')->nullable();
            $table->json('theme')->nullable();

            $table->timestamps();

            $table->index('business_id');
            $table->index('slug');
        });

        // 2. store_categories
        Schema::create('store_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();

            $table->string('name');
            $table->string('slug');
            $table->string('image_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('store_categories')->nullOnDelete();
            $table->index(['store_id', 'is_active']);
            $table->unique(['store_id', 'slug']);
        });

        // 3. store_products
        Schema::create('store_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('store_categories')->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->decimal('compare_price', 15, 2)->nullable();
            $table->string('sku')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('track_stock')->default(true);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'category_id']);
            $table->index(['store_id', 'is_featured']);
            $table->unique(['store_id', 'slug']);
        });

        // 4. store_product_images
        Schema::create('store_product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('store_products')->cascadeOnDelete();

            $table->string('image_url');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        // 5. store_product_variants
        Schema::create('store_product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('store_products')->cascadeOnDelete();

            $table->string('name');
            $table->string('sku')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('stock_quantity')->default(0);
            $table->json('attributes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });

        // 6. store_customers
        Schema::create('store_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('telegram_user_id')->nullable()->constrained('telegram_users')->nullOnDelete();

            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->json('address')->nullable();

            $table->integer('orders_count')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->timestamp('last_order_at')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'telegram_user_id']);
            $table->index(['store_id', 'phone']);
        });

        // 7. store_orders
        Schema::create('store_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('store_customers')->cascadeOnDelete();

            $table->string('order_number', 30)->unique();
            $table->string('status', 30)->default('pending');
            // pending, confirmed, processing, shipped, delivered, cancelled, refunded

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('delivery_fee', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->string('payment_method', 30)->nullable(); // payme, click, cash
            $table->string('payment_status', 30)->default('pending'); // pending, paid, refunded

            $table->json('delivery_address')->nullable();
            $table->text('notes')->nullable();
            $table->string('promo_code')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'created_at']);
            $table->index(['store_id', 'payment_status']);
            $table->index('customer_id');
        });

        // 8. store_order_items
        Schema::create('store_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('store_orders')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('store_products')->nullOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('store_product_variants')->nullOnDelete();

            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total', 15, 2);

            $table->timestamps();

            $table->index('order_id');
        });

        // 9. store_order_status_history
        Schema::create('store_order_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('store_orders')->cascadeOnDelete();

            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('comment')->nullable();
            $table->foreignUuid('changed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('order_id');
        });

        // 10. store_carts
        Schema::create('store_carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->foreignUuid('customer_id')->nullable()->constrained('store_customers')->nullOnDelete();

            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'session_id']);
            $table->index(['store_id', 'customer_id']);
        });

        // 11. store_cart_items
        Schema::create('store_cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->constrained('store_carts')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('store_products')->cascadeOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('store_product_variants')->nullOnDelete();

            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2);

            $table->timestamps();

            $table->index('cart_id');
            $table->unique(['cart_id', 'product_id', 'variant_id']);
        });

        // 12. store_promo_codes
        Schema::create('store_promo_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('type', 20)->default('fixed'); // fixed, percent
            $table->decimal('value', 15, 2);
            $table->decimal('min_order_amount', 15, 2)->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('used_count')->default(0);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['store_id', 'code']);
            $table->index(['store_id', 'is_active']);
        });

        // 13. store_reviews
        Schema::create('store_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('store_products')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('store_customers')->cascadeOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained('store_orders')->nullOnDelete();

            $table->tinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(false);

            $table->timestamps();

            $table->index(['product_id', 'is_approved']);
            $table->index(['store_id', 'created_at']);
        });

        // 14. store_payment_transactions
        Schema::create('store_payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained('store_orders')->cascadeOnDelete();

            $table->string('provider', 30); // payme, click, cash
            $table->string('provider_transaction_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('UZS');
            $table->string('status', 30)->default('pending');
            // pending, processing, completed, cancelled, failed, refunded

            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['order_id']);
            $table->index(['provider', 'provider_transaction_id'], 'store_pay_tx_provider_idx');
        });

        // 15. store_delivery_zones
        Schema::create('store_delivery_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();

            $table->string('name');
            $table->decimal('delivery_fee', 15, 2)->default(0);
            $table->decimal('min_order_amount', 15, 2)->nullable();
            $table->string('estimated_time')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['store_id', 'is_active']);
        });

        // 16. store_analytics_daily
        Schema::create('store_analytics_daily', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();

            $table->date('date');
            $table->integer('views')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('orders_count')->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->decimal('avg_order_value', 15, 2)->default(0);
            $table->integer('new_customers')->default(0);
            $table->integer('returning_customers')->default(0);

            $table->timestamps();

            $table->unique(['store_id', 'date']);
            $table->index(['store_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_analytics_daily');
        Schema::dropIfExists('store_delivery_zones');
        Schema::dropIfExists('store_payment_transactions');
        Schema::dropIfExists('store_reviews');
        Schema::dropIfExists('store_promo_codes');
        Schema::dropIfExists('store_cart_items');
        Schema::dropIfExists('store_carts');
        Schema::dropIfExists('store_order_status_history');
        Schema::dropIfExists('store_order_items');
        Schema::dropIfExists('store_orders');
        Schema::dropIfExists('store_customers');
        Schema::dropIfExists('store_product_variants');
        Schema::dropIfExists('store_product_images');
        Schema::dropIfExists('store_products');
        Schema::dropIfExists('store_categories');
        Schema::dropIfExists('telegram_stores');
    }
};
