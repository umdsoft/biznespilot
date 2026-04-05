<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('short_desc')->nullable();
            $table->string('category')->default('product'); // product, service, course, subscription, other
            $table->decimal('price', 15, 2)->default(0);
            $table->text('advantages')->nullable();
            $table->text('weaknesses')->nullable();
            $table->string('target_audience')->nullable();
            $table->integer('usp_score')->default(0); // 0-100
            $table->string('competition')->default('medium'); // low, medium, high
            $table->string('marketing_status')->default('none'); // active, planned, paused, none
            $table->decimal('market_avg_price', 15, 2)->nullable();
            $table->integer('advantages_count')->default(0);
            $table->integer('weaknesses_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_analyses');
    }
};
