<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offers (Value Proposition)
        Schema::create('offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('dream_buyer_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 50)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->default('UZS');
            // Value Equation
            $table->integer('dream_outcome_score')->nullable();
            $table->text('dream_outcome_description')->nullable();
            $table->integer('perceived_likelihood_score')->nullable();
            $table->text('perceived_likelihood_description')->nullable();
            $table->integer('time_delay_score')->nullable();
            $table->text('time_delay_description')->nullable();
            $table->integer('effort_sacrifice_score')->nullable();
            $table->text('effort_sacrifice_description')->nullable();
            $table->decimal('value_score', 5, 2)->nullable();
            $table->json('bonuses')->nullable();
            $table->json('guarantees')->nullable();
            $table->json('urgency_scarcity')->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('dream_buyer_id')->references('id')->on('dream_buyers')->onDelete('set null');

            $table->index('business_id');
            $table->index('is_active');
            $table->softDeletes();
        });

        // Offer Components
        Schema::create('offer_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('offer_id');
            $table->string('type', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('value', 12, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('offer_id')->references('id')->on('offers')->onDelete('cascade');
            $table->index('offer_id');
        });

        // HVCOs (High Value Content Offers)
        Schema::create('hvcos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('offer_id')->nullable();
            $table->string('name');
            $table->string('type', 50); // lead_magnet, webinar, ebook, etc.
            $table->text('description')->nullable();
            $table->string('delivery_method', 50)->nullable();
            $table->string('file_url')->nullable();
            $table->string('landing_page_url')->nullable();
            $table->integer('downloads_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('offer_id')->references('id')->on('offers')->onDelete('set null');

            $table->index('business_id');
            $table->index('type');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hvcos');
        Schema::dropIfExists('offer_components');
        Schema::dropIfExists('offers');
    }
};
