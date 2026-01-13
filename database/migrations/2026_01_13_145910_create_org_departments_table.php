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
        Schema::create('org_departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('org_structure_id')->constrained('org_structures')->onDelete('cascade');
            $table->foreignId('department_template_id')->nullable()->constrained('department_templates'); // Template reference
            $table->string('name'); // Custom name (agar template'dan farq qilsa)
            $table->string('code')->nullable(); // hr, marketing, education, etc
            $table->string('color')->default('#6B7280');
            $table->string('icon')->nullable();
            $table->text('yqm_description')->nullable(); // Custom YQM (agar template'dan farq qilsa)
            $table->foreignUuid('parent_id')->nullable()->constrained('org_departments')->onDelete('set null'); // Ierarxiya uchun
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['org_structure_id', 'parent_id']);
            $table->index(['org_structure_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_departments');
    }
};
