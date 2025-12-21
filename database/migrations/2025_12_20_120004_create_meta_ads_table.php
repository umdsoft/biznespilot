<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_set_id')->constrained('meta_ad_sets')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('meta_ad_id');
            $table->string('name');
            $table->string('status');
            $table->string('effective_status')->nullable();
            $table->string('creative_id')->nullable();
            $table->string('creative_thumbnail_url')->nullable();
            $table->text('creative_body')->nullable();
            $table->string('creative_title')->nullable();
            $table->string('creative_link_url')->nullable();
            $table->string('creative_call_to_action')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['ad_set_id', 'meta_ad_id']);
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ads');
    }
};
