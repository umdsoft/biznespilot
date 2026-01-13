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
        Schema::create('job_descriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('title');
            $table->string('department');
            $table->string('position_level')->nullable();
            $table->string('reports_to')->nullable();
            $table->text('job_summary')->nullable();
            $table->text('responsibilities')->nullable();
            $table->text('requirements')->nullable();
            $table->text('qualifications')->nullable();
            $table->text('skills')->nullable();
            $table->decimal('salary_range_min', 12, 2)->nullable();
            $table->decimal('salary_range_max', 12, 2)->nullable();
            $table->string('employment_type')->default('full_time');
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_descriptions');
    }
};
