<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->date('date');
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->decimal('work_hours', 4, 2)->default(0);
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'wfh', 'leave'])->default('present');
            $table->text('notes')->nullable();
            $table->string('location')->nullable(); // GPS location or office name
            $table->string('ip_address')->nullable();
            $table->timestamps();

            // Indexes
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['business_id', 'user_id', 'date']);
            $table->index(['business_id', 'date']);
            $table->unique(['business_id', 'user_id', 'date']); // One record per user per day
        });

        // Attendance Settings Table
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->unique();
            $table->time('work_start_time')->default('09:00:00');
            $table->time('work_end_time')->default('18:00:00');
            $table->integer('work_hours_per_day')->default(8);
            $table->integer('late_threshold_minutes')->default(15); // Consider late after X minutes
            $table->boolean('require_location')->default(false);
            $table->boolean('allow_remote_checkin')->default(true);
            $table->json('office_locations')->nullable(); // Array of allowed office locations
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        // Attendance Summary (Monthly aggregated data)
        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->integer('year');
            $table->integer('month');
            $table->integer('total_working_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('late_days')->default(0);
            $table->integer('half_days')->default(0);
            $table->integer('wfh_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->decimal('total_work_hours', 6, 2)->default(0);
            $table->decimal('attendance_percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['business_id', 'year', 'month']);
            $table->unique(['business_id', 'user_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
        Schema::dropIfExists('attendance_settings');
        Schema::dropIfExists('attendance_records');
    }
};
