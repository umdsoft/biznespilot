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
        Schema::create('moizvonki_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name')->default('Moi Zvonki');
            $table->string('email'); // Login email
            $table->string('api_url'); // company.moizvonki.ru
            $table->string('api_key'); // API key from settings
            $table->boolean('is_active')->default(true);
            $table->integer('balance')->default(0);
            $table->json('settings')->nullable(); // Additional settings
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moizvonki_accounts');
    }
};
