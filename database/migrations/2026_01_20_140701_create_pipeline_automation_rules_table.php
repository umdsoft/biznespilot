<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_automation_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            // Trigger
            $table->string('trigger_type'); // call_log_created, task_created, task_completed, sale_created, lead_lost
            $table->json('trigger_conditions')->nullable();
            // Masalan: {"direction": "outbound", "status": "answered"}
            // Masalan: {"type": "meeting"}

            // Action - slug asosida (mavjud arxitekturaga mos)
            $table->string('from_stage_slug')->nullable(); // Faqat shu stage dan o'tsin
            $table->string('to_stage_slug'); // Maqsad stage

            // Options
            $table->boolean('only_if_current_stage')->default(false);
            $table->boolean('prevent_backward')->default(true); // Orqaga o'tishni oldini olish
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);

            $table->timestamps();

            // Indexes (qisqa nomlar MySQL uchun)
            $table->index(['business_id', 'trigger_type', 'is_active'], 'par_biz_trigger_active_idx');
            $table->index(['business_id', 'from_stage_slug'], 'par_biz_from_stage_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_automation_rules');
    }
};
