<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Salary Structures Table
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->decimal('base_salary', 12, 2);
            $table->string('currency')->default('UZS');
            $table->string('payment_frequency'); // monthly, bi-weekly, weekly
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('allowances')->nullable(); // Housing, Transport, Meal, etc.
            $table->json('deductions')->nullable(); // Tax, Insurance, etc.
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Payroll Cycles Table
        Schema::create('payroll_cycles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('period'); // 2024-01, 2024-Q1, etc.
            $table->date('start_date');
            $table->date('end_date');
            $table->date('payment_date');
            $table->string('status')->default('draft'); // draft, processing, approved, paid
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
        });

        // Payslips Table
        Schema::create('payslips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('payroll_cycle_id');
            $table->uuid('user_id');
            $table->uuid('salary_structure_id');
            $table->decimal('base_salary', 12, 2);
            $table->integer('working_days');
            $table->integer('days_worked');
            $table->integer('absent_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->json('allowances')->nullable();
            $table->decimal('total_allowances', 12, 2)->default(0);
            $table->json('deductions')->nullable();
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('net_salary', 12, 2);
            $table->string('payment_method')->nullable(); // bank_transfer, cash, check
            $table->string('payment_status')->default('pending'); // pending, paid, failed
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('payroll_cycle_id')->references('id')->on('payroll_cycles')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('salary_structure_id')->references('id')->on('salary_structures')->onDelete('cascade');
            $table->unique(['payroll_cycle_id', 'user_id']);
        });

        // Bonuses Table
        Schema::create('bonuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->string('type'); // performance, annual, spot, referral
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('granted_date');
            $table->uuid('payroll_cycle_id')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->uuid('approved_by');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('payroll_cycle_id')->references('id')->on('payroll_cycles')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Salary History Table (for audit trail)
        Schema::create('salary_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->decimal('old_salary', 12, 2);
            $table->decimal('new_salary', 12, 2);
            $table->decimal('change_amount', 12, 2);
            $table->decimal('change_percentage', 8, 2);
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->date('effective_date');
            $table->uuid('changed_by');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_history');
        Schema::dropIfExists('bonuses');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_cycles');
        Schema::dropIfExists('salary_structures');
    }
};
