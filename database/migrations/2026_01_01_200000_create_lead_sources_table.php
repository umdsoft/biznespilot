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
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_id')->nullable(); // NULL = global, otherwise business-specific
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('category', ['digital', 'offline', 'referral', 'organic'])->default('digital');
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('is_paid')->default(true);
            $table->decimal('default_cost', 15, 2)->nullable(); // Default CPL
            $table->boolean('is_trackable')->default(false); // Can be auto-tracked via integration
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_sources');
    }
};
