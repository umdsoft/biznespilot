<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. queue_services
        Schema::create('queue_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->integer('duration_min');
            $table->integer('duration_max');
            $table->decimal('price', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('requires_branch')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active', 'sort_order']);
            $table->unique(['business_id', 'slug']);
        });

        // 2. queue_branches
        Schema::create('queue_branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('address');
            $table->string('phone', 20)->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->json('working_hours')->nullable();
            $table->json('lunch_break')->nullable();
            $table->integer('slot_duration')->default(30)->comment('minutes');
            $table->integer('max_concurrent')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
        });

        // 3. queue_branch_services (pivot)
        Schema::create('queue_branch_services', function (Blueprint $table) {
            $table->foreignUuid('branch_id')->constrained('queue_branches')->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained('queue_services')->cascadeOnDelete();
            $table->decimal('custom_price', 12, 2)->nullable();
            $table->integer('custom_duration')->nullable();

            $table->primary(['branch_id', 'service_id']);
        });

        // 4. queue_specialists
        Schema::create('queue_specialists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('queue_branches')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->string('specialization')->nullable();
            $table->text('bio')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'branch_id', 'is_active']);
        });

        // 5. queue_specialist_services (pivot)
        Schema::create('queue_specialist_services', function (Blueprint $table) {
            $table->foreignUuid('specialist_id')->constrained('queue_specialists')->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained('queue_services')->cascadeOnDelete();

            $table->primary(['specialist_id', 'service_id']);
        });

        // 6. queue_time_slots
        Schema::create('queue_time_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('queue_branches')->cascadeOnDelete();
            $table->uuid('specialist_id')->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['available', 'booked', 'blocked', 'lunch'])->default('available');
            $table->uuid('booking_id')->nullable();
            $table->timestamps();

            $table->foreign('specialist_id')->references('id')->on('queue_specialists')->nullOnDelete();
            $table->index(['branch_id', 'date', 'status']);
            $table->index(['specialist_id', 'date', 'status']);
            $table->index(['branch_id', 'specialist_id', 'date']);
        });

        // 7. queue_bookings
        Schema::create('queue_bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('booking_number', 50)->unique();
            $table->bigInteger('telegram_user_id');
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->foreignUuid('service_id')->constrained('queue_services');
            $table->foreignUuid('branch_id')->constrained('queue_branches');
            $table->uuid('specialist_id')->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('queue_number')->comment('Daily sequential number');
            $table->enum('status', [
                'pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show',
            ])->default('pending');
            $table->integer('people_ahead')->default(0);
            $table->integer('estimated_wait')->nullable()->comment('minutes');
            $table->decimal('price', 12, 2)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'not_required'])->default('not_required');
            $table->enum('payment_method', ['cash', 'card', 'click', 'payme'])->nullable();
            $table->text('notes')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('review')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('specialist_id')->references('id')->on('queue_specialists')->nullOnDelete();
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'date']);
            $table->index(['branch_id', 'date', 'status']);
            $table->index('telegram_user_id');
        });

        // Add FK from time_slots to bookings
        Schema::table('queue_time_slots', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('queue_bookings')->nullOnDelete();
        });

        // 8. queue_settings
        Schema::create('queue_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('allow_same_day')->default(true);
            $table->integer('advance_booking_days')->default(14);
            $table->integer('reminder_minutes_before')->default(30);
            $table->integer('auto_cancel_minutes')->default(15);
            $table->boolean('require_phone')->default(true);
            $table->boolean('allow_specialist_choice')->default(true);
            $table->boolean('show_queue_position')->default(true);
            $table->timestamps();
        });

        // 9. queue_daily_stats
        Schema::create('queue_daily_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->uuid('branch_id')->nullable();
            $table->date('date');
            $table->integer('total_bookings')->default(0);
            $table->integer('completed')->default(0);
            $table->integer('cancelled')->default(0);
            $table->integer('no_shows')->default(0);
            $table->integer('avg_wait_time')->nullable();
            $table->integer('avg_service_time')->nullable();
            $table->string('peak_hour', 5)->nullable();
            $table->uuid('busiest_service_id')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('queue_branches')->nullOnDelete();
            $table->unique(['business_id', 'branch_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_daily_stats');
        Schema::dropIfExists('queue_settings');
        Schema::table('queue_time_slots', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
        });
        Schema::dropIfExists('queue_bookings');
        Schema::dropIfExists('queue_time_slots');
        Schema::dropIfExists('queue_specialist_services');
        Schema::dropIfExists('queue_specialists');
        Schema::dropIfExists('queue_branch_services');
        Schema::dropIfExists('queue_branches');
        Schema::dropIfExists('queue_services');
    }
};
