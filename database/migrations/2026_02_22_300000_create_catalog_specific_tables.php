<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A: Service bot
        Schema::create('store_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('max_capacity')->nullable();
            $table->boolean('requires_staff')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'category_id']);
            $table->unique(['store_id', 'slug']);
        });

        // B: Delivery / Restaurant bot
        Schema::create('store_menu_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('image_url')->nullable();
            $table->unsignedInteger('preparation_time_minutes')->nullable();
            $table->unsignedInteger('calories')->nullable();
            $table->string('portion_size')->nullable();
            $table->json('allergens')->nullable();
            $table->json('dietary_tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'category_id']);
            $table->unique(['store_id', 'slug']);
        });

        Schema::create('store_menu_modifiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('menu_item_id')->constrained('store_menu_items')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['single', 'multiple'])->default('single');
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('min_selections')->default(0);
            $table->unsignedInteger('max_selections')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('store_modifier_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('modifier_id')->constrained('store_menu_modifiers')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // C: Course / Education bot
        Schema::create('store_courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('what_you_learn')->nullable();
            $table->text('requirements')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('duration_hours')->nullable();
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'all'])->default('all');
            $table->string('instructor')->nullable();
            $table->string('instructor_photo')->nullable();
            $table->unsignedInteger('max_students')->nullable();
            $table->unsignedInteger('enrolled_count')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('format', ['online', 'offline', 'hybrid'])->default('online');
            $table->boolean('certificate_included')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'category_id']);
            $table->unique(['store_id', 'slug']);
        });

        Schema::create('store_course_lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained('store_courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('video_url')->nullable();
            $table->text('content')->nullable();
            $table->boolean('is_free_preview')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // D: Fitness bot
        Schema::create('store_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('duration_days')->default(30);
            $table->json('features')->nullable();
            $table->unsignedInteger('max_freezes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->unique(['store_id', 'slug']);
        });

        Schema::create('store_group_classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->unsignedInteger('max_participants')->nullable();
            $table->string('instructor')->nullable();
            $table->string('schedule_text')->nullable();
            $table->json('recurring_schedule')->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced', 'all'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->unique(['store_id', 'slug']);
        });

        // E: Real Estate bot
        Schema::create('store_properties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->enum('price_type', ['sale', 'rent_monthly', 'rent_daily'])->default('sale');
            $table->decimal('area_sqm', 10, 2)->nullable();
            $table->unsignedTinyInteger('rooms')->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedTinyInteger('floor')->nullable();
            $table->unsignedTinyInteger('total_floors')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'category_id']);
            $table->index(['store_id', 'city', 'district']);
            $table->unique(['store_id', 'slug']);
        });

        Schema::create('store_property_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('store_properties')->cascadeOnDelete();
            $table->string('image_url');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // F: Auto bot
        Schema::create('store_vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('mileage_km')->nullable();
            $table->enum('fuel_type', ['petrol', 'diesel', 'gas', 'electric', 'hybrid'])->nullable();
            $table->enum('transmission', ['manual', 'automatic'])->nullable();
            $table->string('color')->nullable();
            $table->decimal('engine_volume', 3, 1)->nullable();
            $table->enum('condition', ['new', 'used', 'certified'])->default('used');
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'brand']);
            $table->unique(['store_id', 'slug']);
        });

        Schema::create('store_vehicle_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vehicle_id')->constrained('store_vehicles')->cascadeOnDelete();
            $table->string('image_url');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // G: Event bot
        Schema::create('store_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('venue')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->unsignedInteger('total_seats')->nullable();
            $table->unsignedInteger('sold_seats')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'start_date']);
            $table->unique(['store_id', 'slug']);
        });

        Schema::create('store_event_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('store_events')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('sold_count')->default(0);
            $table->dateTime('sale_start')->nullable();
            $table->dateTime('sale_end')->nullable();
            $table->unsignedInteger('max_per_order')->default(10);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // H: Travel / Tour bot
        Schema::create('store_tours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->unsignedInteger('duration_days')->default(1);
            $table->string('destination')->nullable();
            $table->string('departure_city')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('max_travelers')->nullable();
            $table->unsignedInteger('booked_count')->default(0);
            $table->json('included')->nullable();
            $table->json('not_included')->nullable();
            $table->enum('difficulty', ['easy', 'moderate', 'challenging', 'extreme'])->default('easy');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'destination']);
            $table->unique(['store_id', 'slug']);
        });

        Schema::create('store_tour_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tour_id')->constrained('store_tours')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->json('activities')->nullable();
            $table->timestamps();
        });

        // I: On-Demand Service bot
        Schema::create('store_service_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->enum('pricing_type', ['fixed', 'hourly', 'per_unit', 'quote'])->default('fixed');
            $table->string('pricing_unit')->nullable();
            $table->unsignedInteger('min_order_amount')->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->json('required_fields')->nullable();
            $table->boolean('requires_address')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->unique(['store_id', 'slug']);
        });

        // J: Subscription / Content bot
        Schema::create('store_content_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->enum('billing_period', ['weekly', 'monthly', 'quarterly', 'yearly', 'lifetime'])->default('monthly');
            $table->unsignedInteger('trial_days')->default(0);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->unique(['store_id', 'slug']);
        });

        Schema::create('store_content_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('plan_id')->constrained('store_content_plans')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('content_type', ['text', 'video', 'audio', 'file', 'link'])->default('text');
            $table->text('content_url')->nullable();
            $table->text('content_body')->nullable();
            $table->boolean('is_locked')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // K: Custom bot
        Schema::create('store_custom_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('telegram_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->json('custom_fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->unique(['store_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_custom_items');
        Schema::dropIfExists('store_content_items');
        Schema::dropIfExists('store_content_plans');
        Schema::dropIfExists('store_service_requests');
        Schema::dropIfExists('store_tour_days');
        Schema::dropIfExists('store_tours');
        Schema::dropIfExists('store_event_tickets');
        Schema::dropIfExists('store_events');
        Schema::dropIfExists('store_vehicle_images');
        Schema::dropIfExists('store_vehicles');
        Schema::dropIfExists('store_property_images');
        Schema::dropIfExists('store_properties');
        Schema::dropIfExists('store_group_classes');
        Schema::dropIfExists('store_memberships');
        Schema::dropIfExists('store_course_lessons');
        Schema::dropIfExists('store_courses');
        Schema::dropIfExists('store_modifier_options');
        Schema::dropIfExists('store_menu_modifiers');
        Schema::dropIfExists('store_menu_items');
        Schema::dropIfExists('store_services');
    }
};
