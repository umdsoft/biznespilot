<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Narx monitoringi - mahsulot narxlari, aksiyalar
     */
    public function up(): void
    {
        // Products/Services being tracked
        Schema::create('competitor_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competitor_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('sku')->nullable(); // External product code
            $table->string('url')->nullable(); // Product page URL
            $table->string('image_url')->nullable();

            // Current pricing
            $table->decimal('current_price', 14, 2)->nullable();
            $table->decimal('original_price', 14, 2)->nullable(); // Before discount
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->string('currency', 10)->default('UZS');
            $table->boolean('is_on_sale')->default(false);
            $table->string('sale_label')->nullable(); // "20% off", "Black Friday"

            // Stock status
            $table->string('stock_status', 50)->default('unknown'); // in_stock, out_of_stock, low_stock
            $table->integer('stock_quantity')->nullable();

            // Tracking settings
            $table->boolean('is_tracked')->default(true);
            $table->string('source', 100)->nullable(); // website, telegram, instagram
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('price_changed_at')->nullable();

            $table->timestamps();

            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->index(['competitor_id', 'category']);
            $table->index(['competitor_id', 'is_tracked']);
        });

        // Price history
        Schema::create('competitor_price_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->decimal('price', 14, 2);
            $table->decimal('original_price', 14, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->boolean('is_on_sale')->default(false);
            $table->string('stock_status', 50)->nullable();
            $table->string('currency', 10)->default('UZS');
            $table->date('recorded_date');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('competitor_products')->onDelete('cascade');
            $table->index(['product_id', 'recorded_date']);
        });

        // Promotions/Sales tracking
        Schema::create('competitor_promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competitor_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('promo_type', 50); // sale, discount, bundle, free_shipping, flash_sale
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->string('discount_type', 20)->nullable(); // percent, fixed
            $table->string('promo_code')->nullable();

            // Timing
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);

            // Detection
            $table->string('detected_from', 100)->nullable(); // instagram, telegram, website
            $table->string('source_url')->nullable();
            $table->json('affected_categories')->nullable();
            $table->json('affected_products')->nullable();

            $table->timestamps();

            $table->foreign('competitor_id')->references('id')->on('competitors')->onDelete('cascade');
            $table->index(['competitor_id', 'is_active']);
            $table->index(['competitor_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_promotions');
        Schema::dropIfExists('competitor_price_history');
        Schema::dropIfExists('competitor_products');
    }
};
