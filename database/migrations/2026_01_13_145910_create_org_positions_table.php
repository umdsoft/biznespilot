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
        Schema::create('org_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('org_department_id')->constrained('org_departments')->onDelete('cascade');
            $table->foreignId('position_template_id')->nullable()->constrained('position_templates'); // Template reference
            $table->foreignUuid('job_description_id')->nullable()->constrained('job_descriptions'); // Batafsil tavsif
            $table->string('title'); // Custom title
            $table->integer('level')->default(3); // 0=Director, 1=Head, 2=Manager, 3=Specialist, 4=Junior

            // YQM
            $table->text('yqm_primary')->nullable(); // Custom YQM
            $table->text('yqm_description')->nullable();
            $table->json('yqm_metrics')->nullable();

            // Staffing
            $table->integer('required_count')->default(1); // Nechta odam kerak
            $table->integer('current_count')->default(0); // Hozir nechta

            // Salary
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();

            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['org_department_id', 'is_active']);
            $table->index(['required_count', 'current_count']); // For fill rate calculations
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_positions');
    }
};
