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
        Schema::create('sales_channels', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_id')->nullable(); // NULL = global
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('type', ['retail', 'online', 'wholesale', 'agent', 'b2b'])->default('retail');
            $table->string('icon', 50)->nullable();
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_channels');
    }
};
