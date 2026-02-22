<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staff management (service, fitness, beauty botlar uchun)
        Schema::create('store_staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('position')->nullable();
            $table->text('bio')->nullable();
            $table->json('specializations')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
        });

        Schema::create('store_staff_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')->constrained('store_staff')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Monday ... 6=Sunday
            $table->time('start_time');
            $table->time('end_time');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->boolean('is_working')->default(true);
            $table->timestamps();

            $table->unique(['staff_id', 'day_of_week']);
        });

        Schema::create('store_staff_time_off', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')->constrained('store_staff')->cascadeOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        // Polimorfik booking (service, event, class, tour uchun)
        Schema::create('store_bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('store_customers')->nullOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained('store_orders')->nullOnDelete();
            $table->nullableUuidMorphs('bookable'); // bookable_type + bookable_id
            $table->foreignUuid('staff_id')->nullable()->constrained('store_staff')->nullOnDelete();
            $table->dateTime('booked_at');
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('guests_count')->default(1);
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'booked_at']);
            $table->index(['staff_id', 'booked_at']);
            $table->index(['customer_id']);
        });

        // Polimorfik wishlist
        Schema::create('store_wishlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('store_customers')->cascadeOnDelete();
            $table->nullableUuidMorphs('wishlistable');
            $table->timestamps();

            $table->index(['customer_id']);
            $table->index(['store_id', 'customer_id']);
        });

        // Customer addresses (delivery, ondemand uchun)
        Schema::create('store_customer_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('store_customers')->cascadeOnDelete();
            $table->string('label')->nullable(); // "Uy", "Ish"
            $table->string('full_address');
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('street')->nullable();
            $table->string('building')->nullable();
            $table->string('apartment')->nullable();
            $table->string('entrance')->nullable();
            $table->string('floor')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['customer_id']);
        });

        // Refunds
        Schema::create('store_refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('store_orders')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
            $table->text('reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('refund_method')->nullable();
            $table->string('refund_reference')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['order_id']);
        });

        // Review replies
        Schema::create('store_review_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('review_id')->constrained('store_reviews')->cascadeOnDelete();
            $table->text('reply');
            $table->string('replied_by')->nullable(); // admin name
            $table->timestamps();
        });

        // Loyalty program
        Schema::create('store_loyalty_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('points_per_currency', 8, 2)->default(1); // 1 so'm = 1 ball
            $table->decimal('currency_per_point', 8, 2)->default(1); // 1 ball = 1 so'm
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['store_id']);
        });

        Schema::create('store_loyalty_tiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained('store_loyalty_programs')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('min_points')->default(0);
            $table->decimal('multiplier', 5, 2)->default(1); // ball koeffitsiyenti
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->json('perks')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('store_loyalty_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained('store_loyalty_programs')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('store_customers')->cascadeOnDelete();
            $table->enum('type', ['earn', 'redeem', 'adjust', 'expire']);
            $table->integer('points');
            $table->unsignedInteger('balance_after')->default(0);
            $table->string('description')->nullable();
            $table->foreignUuid('order_id')->nullable()->constrained('store_orders')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id']);
            $table->index(['program_id', 'customer_id']);
        });

        Schema::create('store_loyalty_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained('store_loyalty_programs')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('points_required');
            $table->enum('reward_type', ['discount_percent', 'discount_fixed', 'free_product', 'free_shipping', 'custom']);
            $table->decimal('reward_value', 12, 2)->nullable();
            $table->unsignedInteger('stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('store_loyalty_redemptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reward_id')->constrained('store_loyalty_rewards')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('store_customers')->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->nullable()->constrained('store_loyalty_transactions')->nullOnDelete();
            $table->unsignedInteger('points_spent');
            $table->enum('status', ['pending', 'applied', 'expired', 'cancelled'])->default('pending');
            $table->string('coupon_code')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_loyalty_redemptions');
        Schema::dropIfExists('store_loyalty_rewards');
        Schema::dropIfExists('store_loyalty_transactions');
        Schema::dropIfExists('store_loyalty_tiers');
        Schema::dropIfExists('store_loyalty_programs');
        Schema::dropIfExists('store_review_replies');
        Schema::dropIfExists('store_refunds');
        Schema::dropIfExists('store_customer_addresses');
        Schema::dropIfExists('store_wishlists');
        Schema::dropIfExists('store_bookings');
        Schema::dropIfExists('store_staff_time_off');
        Schema::dropIfExists('store_staff_schedules');
        Schema::dropIfExists('store_staff');
    }
};
