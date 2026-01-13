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
        Schema::create('position_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_template_id')->constrained('department_templates')->onDelete('cascade');
            $table->string('code')->unique(); // hr_manager, marketing_smm, teacher, etc
            $table->string('title_uz');
            $table->string('title_ru')->nullable();
            $table->string('title_en')->nullable();
            $table->integer('level')->default(3); // 0=Director, 1=Head, 2=Manager, 3=Specialist, 4=Junior
            $table->string('reports_to')->nullable(); // Bo'lim boshlig'i, Direktor, etc

            // YQM (Yakuniy Qiymatdagi Maxsulot)
            $table->text('yqm_primary'); // Asosiy mahsulot/natija
            $table->text('yqm_description')->nullable(); // Batafsil tavsif
            $table->json('yqm_metrics')->nullable(); // O'lchov ko'rsatkichlari

            // Mas'uliyatlar
            $table->json('responsibilities')->nullable(); // daily, weekly, monthly tasks
            $table->json('success_criteria')->nullable(); // 30 days, 90 days, 1 year
            $table->json('requirements')->nullable(); // must_have, nice_to_have

            // Qo'shimcha
            $table->integer('default_count')->default(1); // Template'da nechta odam tavsiya etiladi
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['department_template_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_templates');
    }
};
