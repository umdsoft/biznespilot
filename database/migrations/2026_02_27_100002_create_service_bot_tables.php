<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. service_categories
        Schema::create('service_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('icon', 100)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active', 'sort_order']);
            $table->unique(['business_id', 'slug']);
        });

        // 2. service_types
        Schema::create('service_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_from', 12, 2);
            $table->decimal('price_to', 12, 2)->nullable();
            $table->string('estimated_duration', 50)->nullable();
            $table->integer('warranty_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'category_id', 'is_active']);
        });

        // 3. service_masters
        Schema::create('service_masters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('avatar_url', 500)->nullable();
            $table->json('specializations')->nullable();
            $table->integer('experience_years')->default(0);
            $table->text('bio')->nullable();
            $table->integer('warranty_months')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('completed_jobs')->default(0);
            $table->decimal('hourly_rate', 12, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->time('available_from')->nullable();
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active', 'is_available']);
        });

        // 4. service_master_categories (pivot)
        Schema::create('service_master_categories', function (Blueprint $table) {
            $table->foreignUuid('master_id')->constrained('service_masters')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('service_categories')->cascadeOnDelete();

            $table->primary(['master_id', 'category_id']);
        });

        // 5. service_requests
        Schema::create('service_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('request_number', 50)->unique();
            $table->bigInteger('telegram_user_id');
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->foreignUuid('category_id')->constrained('service_categories');
            $table->foreignUuid('service_type_id')->constrained('service_types');
            $table->uuid('master_id')->nullable();
            $table->enum('status', [
                'pending', 'assigned', 'en_route', 'arrived', 'diagnosing', 'in_progress', 'completed', 'cancelled',
            ])->default('pending');
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->text('address');
            $table->string('landmark')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time_slot', 50)->nullable()->comment('morning/afternoon/evening/anytime');
            $table->text('diagnosis_notes')->nullable();
            $table->text('work_description')->nullable();
            $table->json('parts_used')->nullable()->comment('[{name, price}]');
            $table->decimal('labor_cost', 12, 2)->nullable();
            $table->decimal('parts_cost', 12, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->boolean('cost_approved')->default(false);
            $table->enum('payment_method', ['cash', 'card', 'click', 'payme'])->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'not_required'])->default('pending');
            $table->date('warranty_until')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('review')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('en_route_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('diagnosing_at')->nullable();
            $table->timestamp('in_progress_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('master_id')->references('id')->on('service_masters')->nullOnDelete();
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'created_at']);
            $table->index(['master_id', 'status']);
            $table->index('telegram_user_id');
        });

        // 6. service_settings
        Schema::create('service_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('auto_assign_master')->default(false);
            $table->boolean('allow_master_choice')->default(true);
            $table->boolean('require_cost_approval')->default(true);
            $table->boolean('show_master_location')->default(false);
            $table->integer('max_images')->default(5);
            $table->json('working_hours')->nullable();
            $table->json('service_area')->nullable();
            $table->timestamps();
        });

        // 7. service_daily_stats
        Schema::create('service_daily_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('total_requests')->default(0);
            $table->integer('completed')->default(0);
            $table->integer('cancelled')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('avg_completion_time')->nullable()->comment('minutes');
            $table->json('top_categories')->nullable();
            $table->json('top_masters')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_daily_stats');
        Schema::dropIfExists('service_settings');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('service_master_categories');
        Schema::dropIfExists('service_masters');
        Schema::dropIfExists('service_types');
        Schema::dropIfExists('service_categories');
    }
};
