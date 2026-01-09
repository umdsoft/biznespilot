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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('lead_id')->nullable();
            $table->uuid('user_id'); // Yaratuvchi
            $table->uuid('assigned_to')->nullable(); // Tayinlangan

            // Task details
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['call', 'meeting', 'email', 'task', 'follow_up', 'other'])->default('task');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');

            // Dates
            $table->dateTime('due_date');
            $table->dateTime('reminder_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            // Result
            $table->text('result')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'due_date']);
            $table->index(['assigned_to', 'status']);
            $table->index(['lead_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
