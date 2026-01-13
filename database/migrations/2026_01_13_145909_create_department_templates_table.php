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
        Schema::create('department_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // hr, finance, marketing, education, etc
            $table->string('name_uz');
            $table->string('name_ru')->nullable();
            $table->string('name_en')->nullable();
            $table->string('icon')->nullable(); // Icon name
            $table->string('color')->default('#6B7280'); // Hex color
            $table->enum('type', ['static', 'dynamic'])->default('static'); // static = har bir biznes uchun, dynamic = biznes turiga qarab
            $table->foreignId('business_type_id')->nullable()->constrained('business_types')->onDelete('cascade'); // Agar dynamic bo'lsa
            $table->text('yqm_description')->nullable(); // YQM (Yakuniy Qiymatdagi Maxsulot) tavsifi
            $table->json('responsibilities')->nullable(); // Asosiy mas'uliyatlar
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index for faster queries
            $table->index(['type', 'business_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_templates');
    }
};
