<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales skriptlari jadvali.
 * Biznes o'z savdo skriptini yaratadi — operator unga amal qilishi kerak.
 *
 * Skript tarkibi:
 *   - 7 bosqich (greeting, discovery, ..., cta)
 *   - Har bosqichda: required_phrases (majburiy), forbidden_phrases (taqiqlangan)
 *   - Misol matnlar va tips
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_scripts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('script_type', ['inbound', 'outbound', 'follow_up', 'general'])->default('general');

            // Bosqichlar tarkibi (JSON)
            // Struktura: { greeting: { required: [], forbidden: [], tips: [], example: '' }, ... }
            $table->json('stages')->nullable();

            // Umumiy majburiy frazalar (har bosqichda emas, umuman)
            $table->json('global_required_phrases')->nullable();
            $table->json('global_forbidden_phrases')->nullable();

            // Standart sozlamalar
            $table->integer('ideal_duration_min')->default(120)->comment('soniyalarda');
            $table->integer('ideal_duration_max')->default(600);
            $table->decimal('ideal_talk_ratio_min', 5, 2)->default(30)->comment('operator kamida 30% gapirsin');
            $table->decimal('ideal_talk_ratio_max', 5, 2)->default(60)->comment('operator ko\'pi 60% gapirsin');

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->uuid('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
            $table->index(['business_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_scripts');
    }
};
