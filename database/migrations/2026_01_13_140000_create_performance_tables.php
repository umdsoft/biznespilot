<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // HR KPI Templates Table
        Schema::create('hr_kpi_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); // sales, productivity, quality, customer_satisfaction
            $table->string('measurement_unit'); // percentage, number, currency
            $table->decimal('target_value', 12, 2)->nullable();
            $table->string('frequency'); // daily, weekly, monthly, quarterly, annually
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        // HR Employee Goals Table
        Schema::create('hr_employee_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->uuid('kpi_template_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('due_date');
            $table->string('status')->default('active'); // active, completed, cancelled, overdue
            $table->integer('progress')->default(0); // 0-100
            $table->decimal('target_value', 12, 2)->nullable();
            $table->decimal('current_value', 12, 2)->default(0);
            $table->string('measurement_unit')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('kpi_template_id')->references('id')->on('hr_kpi_templates')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // HR Performance Reviews Table
        Schema::create('hr_performance_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->uuid('reviewer_id');
            $table->string('review_period'); // Q1 2024, 2024 Annual, etc.
            $table->date('review_date');
            $table->string('status')->default('draft'); // draft, submitted, completed
            $table->integer('overall_rating')->nullable(); // 1-5
            $table->json('ratings')->nullable(); // Individual criteria ratings
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('achievements')->nullable();
            $table->text('goals_for_next_period')->nullable();
            $table->text('manager_comments')->nullable();
            $table->text('employee_comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
        });

        // HR One-on-One Meetings Table
        Schema::create('hr_one_on_one_meetings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('employee_id');
            $table->uuid('manager_id');
            $table->datetime('scheduled_at');
            $table->integer('duration_minutes')->default(30);
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled, rescheduled
            $table->json('agenda')->nullable();
            $table->text('employee_notes')->nullable();
            $table->text('manager_notes')->nullable();
            $table->json('action_items')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');
        });

        // HR Feedback Table
        Schema::create('hr_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('from_user_id');
            $table->uuid('to_user_id');
            $table->string('type'); // praise, constructive, request
            $table->text('content');
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_feedback');
        Schema::dropIfExists('hr_one_on_one_meetings');
        Schema::dropIfExists('hr_performance_reviews');
        Schema::dropIfExists('hr_employee_goals');
        Schema::dropIfExists('hr_kpi_templates');
    }
};
