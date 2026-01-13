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
        // Leave Types Table
        Schema::create('leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name'); // Annual, Sick, Family, Unpaid, etc.
            $table->string('code')->unique(); // annual, sick, family, unpaid
            $table->text('description')->nullable();
            $table->integer('default_days_per_year')->default(0); // Default allocation
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_paid')->default(true);
            $table->boolean('carry_forward')->default(false); // Can unused days carry to next year
            $table->integer('max_carry_forward_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('notice_days')->default(0); // Days notice required
            $table->integer('max_consecutive_days')->nullable(); // Max days can take at once
            $table->json('allowed_for_departments')->nullable(); // null = all departments
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'is_active']);
        });

        // Leave Balances Table
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->uuid('leave_type_id');
            $table->integer('year');
            $table->decimal('total_days', 8, 2)->default(0); // Total allocated
            $table->decimal('used_days', 8, 2)->default(0); // Days used
            $table->decimal('pending_days', 8, 2)->default(0); // Days pending approval
            $table->decimal('available_days', 8, 2)->default(0); // Remaining available
            $table->decimal('carried_forward', 8, 2)->default(0); // From previous year
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
            $table->unique(['business_id', 'user_id', 'leave_type_id', 'year']);
            $table->index(['business_id', 'user_id', 'year']);
        });

        // Leave Requests Table
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id'); // Employee requesting leave
            $table->uuid('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 8, 2); // Including weekends if counted
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->uuid('approved_by')->nullable(); // Manager/HR who approved
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->json('attachments')->nullable(); // Medical certificates, etc.
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'user_id', 'status']);
            $table->index(['business_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        // Leave Approval History Table
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leave_request_id');
            $table->uuid('approver_id'); // User who took action
            $table->string('action'); // approved, rejected, requested_changes
            $table->text('comments')->nullable();
            $table->timestamp('actioned_at');
            $table->timestamps();

            $table->foreign('leave_request_id')->references('id')->on('leave_requests')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('leave_request_id');
        });

        // Leave Calendar Events (Optional - for team calendar view)
        Schema::create('leave_calendar_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('leave_request_id');
            $table->uuid('user_id');
            $table->date('event_date');
            $table->boolean('is_full_day')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('leave_request_id')->references('id')->on('leave_requests')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['business_id', 'event_date']);
            $table->index(['user_id', 'event_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_calendar_events');
        Schema::dropIfExists('leave_approvals');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
