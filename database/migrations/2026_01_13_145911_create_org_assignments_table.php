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
        Schema::create('org_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('org_position_id')->constrained('org_positions')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->date('assigned_date')->default(now());
            $table->date('end_date')->nullable(); // Agar vaqtinchalik tayinlangan bo'lsa
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(true); // Bir xodim bir nechta lavozimda bo'lishi mumkin
            $table->json('performance_summary')->nullable(); // YQM progress, KPI achievement, etc
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['org_position_id', 'is_active']);
            $table->index(['user_id', 'business_id']);
            $table->unique(['org_position_id', 'user_id', 'is_active'], 'unique_active_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_assignments');
    }
};
