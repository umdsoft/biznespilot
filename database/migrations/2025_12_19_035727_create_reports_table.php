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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Who generated the report
            $table->enum('type', ['marketing', 'sales', 'financial', 'customer', 'product', 'performance', 'custom'])->default('custom');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('data'); // Report data and results
            $table->json('filters')->nullable(); // Filters applied when generating
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('file_path')->nullable(); // PDF or Excel export
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'type']);
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
