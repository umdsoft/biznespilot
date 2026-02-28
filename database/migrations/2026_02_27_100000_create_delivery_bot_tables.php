<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. delivery_categories
        Schema::create('delivery_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('icon', 100)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->uuid('parent_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('delivery_categories')->nullOnDelete();
            $table->index(['business_id', 'is_active', 'sort_order']);
            $table->unique(['business_id', 'slug']);
        });

        // 2. delivery_menu_items
        Schema::create('delivery_menu_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('delivery_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->decimal('base_price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->integer('preparation_time')->nullable()->comment('minutes');
            $table->integer('calories')->nullable();
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_available', 'sort_order']);
            $table->index(['category_id', 'is_available']);
            $table->unique(['business_id', 'slug']);
        });

        // 3. delivery_item_variants
        Schema::create('delivery_item_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('menu_item_id')->constrained('delivery_menu_items')->cascadeOnDelete();
            $table->string('group_name', 100);
            $table->string('name', 100);
            $table->decimal('price_modifier', 12, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('menu_item_id');
        });

        // 4. delivery_item_addons
        Schema::create('delivery_item_addons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('menu_item_id')->constrained('delivery_menu_items')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('menu_item_id');
        });

        // 5. delivery_orders
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 50)->unique();
            $table->bigInteger('telegram_user_id');
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->enum('status', [
                'pending', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered', 'cancelled',
            ])->default('pending');
            $table->enum('delivery_type', ['delivery', 'pickup'])->default('delivery');
            $table->text('delivery_address')->nullable();
            $table->string('delivery_landmark')->nullable();
            $table->decimal('delivery_lat', 10, 8)->nullable();
            $table->decimal('delivery_lng', 11, 8)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->integer('estimated_delivery')->nullable()->comment('minutes');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('payment_method', ['cash', 'card', 'click', 'payme'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->string('coupon_code', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('courier_phone', 20)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivering_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'created_at']);
            $table->index('telegram_user_id');
        });

        // 6. delivery_order_items
        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->foreignUuid('menu_item_id')->nullable()->constrained('delivery_menu_items')->nullOnDelete();
            $table->string('item_name');
            $table->string('variant_name')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->json('addons')->nullable()->comment('[{name, price}]');
            $table->decimal('addons_total', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->text('special_instructions')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });

        // 7. delivery_addresses
        Schema::create('delivery_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('telegram_user_id');
            $table->string('label', 50);
            $table->text('address');
            $table->string('landmark')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['business_id', 'telegram_user_id']);
        });

        // 8. delivery_settings
        Schema::create('delivery_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('min_order_amount', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(15000);
            $table->decimal('free_delivery_from', 12, 2)->nullable();
            $table->decimal('service_fee_percent', 5, 2)->default(0);
            $table->integer('estimated_delivery_min')->default(30);
            $table->integer('estimated_delivery_max')->default(60);
            $table->json('working_hours')->nullable();
            $table->json('delivery_zones')->nullable();
            $table->boolean('auto_accept_orders')->default(false);
            $table->json('order_notifications')->nullable();
            $table->timestamps();
        });

        // 9. delivery_daily_stats
        Schema::create('delivery_daily_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('total_orders')->default(0);
            $table->integer('completed_orders')->default(0);
            $table->integer('cancelled_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('avg_order_value', 12, 2)->default(0);
            $table->integer('avg_delivery_time')->nullable()->comment('minutes');
            $table->json('top_items')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_daily_stats');
        Schema::dropIfExists('delivery_settings');
        Schema::dropIfExists('delivery_addresses');
        Schema::dropIfExists('delivery_order_items');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('delivery_item_addons');
        Schema::dropIfExists('delivery_item_variants');
        Schema::dropIfExists('delivery_menu_items');
        Schema::dropIfExists('delivery_categories');
    }
};
