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
        Schema::create('lead_score_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            // Oldingi va yangi ball
            $table->unsignedTinyInteger('old_score');
            $table->unsignedTinyInteger('new_score');

            // O'zgarish miqdori (musbat yoki manfiy)
            $table->smallInteger('change_amount');

            // Oldingi va yangi kategoriya
            $table->string('old_category', 20)->nullable();
            $table->string('new_category', 20)->nullable();

            // O'zgarish sababi
            $table->string('reason', 100);

            // Tafsilotlar (qaysi qoida qo'llanilgani, qanday hisoblangani)
            $table->json('details')->nullable();

            // Kim yoki nima tomonidan (user_id, system, scheduled)
            $table->string('triggered_by', 50)->default('system');
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('created_at');

            // Indexlar
            $table->index('lead_id');
            $table->index('created_at');
            $table->index(['business_id', 'created_at']);
            $table->index(['lead_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_score_history');
    }
};
