<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industry_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained()->onDelete('cascade');

            // Metric identification
            $table->string('metric_code', 50);
            $table->string('metric_name_uz');
            $table->string('metric_name_en')->nullable();
            $table->text('description')->nullable();

            // Benchmark values
            $table->decimal('poor_threshold', 15, 4)->nullable();
            $table->decimal('average_value', 15, 4);
            $table->decimal('good_threshold', 15, 4)->nullable();
            $table->decimal('excellent_threshold', 15, 4)->nullable();

            // Context
            $table->string('unit', 50)->default('number');
            $table->enum('direction', ['higher_better', 'lower_better'])->default('higher_better');
            $table->string('source')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint
            $table->unique(['industry_id', 'metric_code']);
            $table->index('metric_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_benchmarks');
    }
};
