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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('value_proposition'); // The unique selling proposition
            $table->text('target_audience')->nullable();
            $table->decimal('pricing', 10, 2)->nullable();
            $table->string('pricing_model')->nullable(); // one-time, recurring, tiered, etc.
            $table->text('guarantees')->nullable();
            $table->text('bonuses')->nullable();
            $table->text('scarcity')->nullable(); // Limited time, limited quantity, etc.
            $table->text('urgency')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->integer('conversion_rate')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
