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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->enum('type', ['physical', 'digital', 'service', 'subscription'])->default('physical');
            $table->string('category')->nullable();
            $table->json('images')->nullable();
            $table->integer('stock_quantity')->nullable();
            $table->boolean('track_inventory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
