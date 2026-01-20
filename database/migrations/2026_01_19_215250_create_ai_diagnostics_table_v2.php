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
        if (!Schema::hasTable('ai_diagnostics')) {
            Schema::create('ai_diagnostics', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('business_id');
                $table->string('type', 50)->default('full');
                $table->string('status', 20)->default('pending');
                $table->json('input_data')->nullable();
                $table->json('results')->nullable();
                $table->decimal('overall_score', 5, 2)->nullable();
                $table->json('recommendations')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
                $table->index('business_id');
                $table->index('type');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_diagnostics');
    }
};
