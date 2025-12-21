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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('price_monthly');
            $table->unsignedInteger('price_yearly');
            $table->unsignedInteger('business_limit')->default(1);
            $table->integer('team_member_limit')->default(1); // -1 = unlimited
            $table->integer('lead_limit')->default(100); // -1 = unlimited
            $table->integer('chatbot_channel_limit')->default(0); // -1 = unlimited
            $table->boolean('has_amocrm')->default(false);
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
