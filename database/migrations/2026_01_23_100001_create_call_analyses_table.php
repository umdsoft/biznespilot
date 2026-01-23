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
        Schema::create('call_analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('call_log_id');

            // Transkript
            $table->longText('transcript')->nullable();

            // Balllar
            $table->decimal('overall_score', 5, 2)->nullable();     // 0-100

            // Bosqich ballari
            $table->json('stage_scores')->nullable();               // {greeting: 85, discovery: 60, ...}

            // Tahlil natijalari
            $table->json('anti_patterns')->nullable();              // [{type, severity, description}, ...]
            $table->json('recommendations')->nullable();            // ["Tavsiya 1", "Tavsiya 2", ...]
            $table->json('strengths')->nullable();                  // ["Kuchli tomon 1", ...]
            $table->json('weaknesses')->nullable();                 // ["Zaif tomon 1", ...]

            // Xarajatlar tracking
            $table->decimal('stt_cost', 10, 6)->default(0);         // USD
            $table->decimal('analysis_cost', 10, 6)->default(0);    // USD
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);

            // Model va processing meta
            $table->string('stt_model')->default('whisper-large-v3-turbo');
            $table->string('analysis_model')->default('claude-3-5-haiku-20241022');
            $table->integer('processing_time_ms')->nullable();

            // Audio vaqtinchalik saqlash
            $table->string('temp_audio_path', 500)->nullable();     // R2 temp path

            $table->timestamps();

            // Foreign key
            $table->foreign('call_log_id')
                ->references('id')
                ->on('call_logs')
                ->onDelete('cascade');

            // Indexes
            $table->index('call_log_id');
            $table->index('overall_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_analyses');
    }
};
