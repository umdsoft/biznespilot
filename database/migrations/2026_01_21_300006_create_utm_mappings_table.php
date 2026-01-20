<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * UTM Mappings - UTM parametrlarni Campaign/Channel ga avtomatik mapping qilish
     */
    public function up(): void
    {
        Schema::create('utm_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');

            // UTM pattern
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 255)->nullable();

            // Mapping target
            $table->uuid('marketing_channel_id')->nullable();
            $table->uuid('campaign_id')->nullable();

            $table->string('name', 255)->nullable(); // Mapping nomi
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Foreign keys
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->cascadeOnDelete();

            $table->foreign('marketing_channel_id')
                ->references('id')
                ->on('marketing_channels')
                ->cascadeOnDelete();

            $table->foreign('campaign_id')
                ->references('id')
                ->on('campaigns')
                ->cascadeOnDelete();

            // Indexes
            $table->index(
                ['business_id', 'utm_source', 'utm_medium', 'utm_campaign'],
                'utm_mapping_lookup_idx'
            );
            $table->index(['business_id', 'is_active'], 'utm_mapping_active_idx');
        });
    }

    /**
     * Rollback
     */
    public function down(): void
    {
        Schema::dropIfExists('utm_mappings');
    }
};
