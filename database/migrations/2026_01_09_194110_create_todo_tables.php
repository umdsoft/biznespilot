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
        // Todo Templates
        Schema::create('todo_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50)->default('custom'); // onboarding, sales, operations, marketing, custom
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'is_active']);
            $table->index(['business_id', 'category']);
        });

        // Todo Template Items
        Schema::create('todo_template_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id');
            $table->uuid('parent_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->string('default_assignee_role', 50)->nullable(); // owner, manager, operator
            $table->integer('due_days_offset')->nullable(); // Days after template applied
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('todo_templates')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('todo_template_items')->onDelete('cascade');
            $table->index(['template_id', 'order']);
        });

        // Main Todos table
        Schema::create('todos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('created_by');
            $table->uuid('assigned_to')->nullable();
            $table->uuid('parent_id')->nullable(); // For sub-tasks
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 20)->default('personal'); // personal, team, process
            $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
            $table->string('status', 20)->default('pending'); // pending, in_progress, completed, cancelled
            $table->dateTime('due_date')->nullable();
            $table->dateTime('reminder_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_recurring')->default(false);
            $table->uuid('recurrence_id')->nullable();
            $table->uuid('template_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_id')->references('id')->on('todos')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('todo_templates')->onDelete('set null');

            // Indexes
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'due_date']);
            $table->index(['business_id', 'type']);
            $table->index(['assigned_to', 'status']);
            $table->index(['created_by', 'status']);
            $table->index(['parent_id', 'order']);
        });

        // Todo Recurrences
        Schema::create('todo_recurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('todo_id'); // Original/template todo
            $table->string('frequency', 20); // daily, weekly, monthly, yearly
            $table->unsignedTinyInteger('interval')->default(1); // Every N days/weeks/months
            $table->json('days_of_week')->nullable(); // [1,3,5] for Mon, Wed, Fri
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1-31
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_occurrence');
            $table->string('generation_mode', 20)->default('on_time'); // advance, on_time
            $table->unsignedInteger('occurrences_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('todo_id')->references('id')->on('todos')->onDelete('cascade');

            $table->index(['business_id', 'is_active']);
            $table->index(['next_occurrence', 'is_active']);
        });

        // Add recurrence_id foreign key to todos (after todo_recurrences is created)
        Schema::table('todos', function (Blueprint $table) {
            $table->foreign('recurrence_id')->references('id')->on('todo_recurrences')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['recurrence_id']);
        });

        Schema::dropIfExists('todo_recurrences');
        Schema::dropIfExists('todos');
        Schema::dropIfExists('todo_template_items');
        Schema::dropIfExists('todo_templates');
    }
};
