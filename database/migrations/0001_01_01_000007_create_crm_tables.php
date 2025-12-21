<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dream Buyers (Ideal Mijoz)
        Schema::create('dream_buyers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('age_range', 50)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('location')->nullable();
            $table->string('occupation')->nullable();
            $table->string('income_level', 50)->nullable();
            $table->text('interests')->nullable();
            $table->text('pain_points')->nullable();
            $table->text('goals')->nullable();
            $table->text('objections')->nullable();
            $table->text('buying_triggers')->nullable();
            $table->text('preferred_channels')->nullable();
            // Nine Questions
            $table->text('q1_who_are_they')->nullable();
            $table->text('q2_what_do_they_want')->nullable();
            $table->text('q3_where_do_they_hang_out')->nullable();
            $table->text('q4_what_keeps_them_up')->nullable();
            $table->text('q5_what_are_they_afraid_of')->nullable();
            $table->text('q6_what_are_they_frustrated_with')->nullable();
            $table->text('q7_what_trends_affect_them')->nullable();
            $table->text('q8_what_do_they_secretly_want')->nullable();
            $table->text('q9_how_do_they_make_decisions')->nullable();
            $table->text('avatar_summary')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('is_primary');
            $table->index('is_active');
            $table->softDeletes();
        });

        // Products
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('currency', 3)->default('UZS');
            $table->string('category')->nullable();
            $table->string('type', 20)->default('product'); // product, service
            $table->integer('stock')->nullable();
            $table->json('images')->nullable();
            $table->json('attributes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
            $table->index('sku');
            $table->index('category');
            $table->index('is_active');
        });

        // Leads
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('dream_buyer_id')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('company')->nullable();
            $table->string('position')->nullable();
            $table->string('source', 50)->nullable();
            $table->string('status', 20)->default('new'); // new, contacted, qualified, proposal, negotiation, won, lost
            $table->string('priority', 20)->default('medium');
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('dream_buyer_id')->references('id')->on('dream_buyers')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

            $table->index('business_id');
            $table->index('status');
            $table->index('source');
            $table->index('created_at');
            $table->index(['business_id', 'status']);
        });

        // Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('lead_id')->nullable();
            $table->uuid('dream_buyer_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('company')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('type', 20)->default('individual'); // individual, company
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->integer('orders_count')->default(0);
            $table->decimal('average_order_value', 12, 2)->default(0);
            $table->timestamp('first_purchase_at')->nullable();
            $table->timestamp('last_purchase_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('dream_buyer_id')->references('id')->on('dream_buyers')->onDelete('set null');

            $table->index('business_id');
            $table->index('email');
            $table->index('phone');
            $table->index('status');
            $table->index(['business_id', 'status']);
        });

        // Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('customer_id')->nullable();
            $table->uuid('lead_id')->nullable();
            $table->string('order_number')->unique();
            $table->string('status', 20)->default('pending');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('currency', 3)->default('UZS');
            $table->string('payment_status', 20)->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');

            $table->index('business_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
            $table->index(['business_id', 'status']);
        });

        // Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('product_id')->nullable();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->json('attributes')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');

            $table->index('order_id');
            $table->index('product_id');
        });

        // Sales (for analytics)
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('order_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->uuid('marketing_channel_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);
            $table->string('currency', 3)->default('UZS');
            $table->date('sale_date');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            $table->index('business_id');
            $table->index('sale_date');
            $table->index(['business_id', 'sale_date']);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('products');
        Schema::dropIfExists('dream_buyers');
    }
};
