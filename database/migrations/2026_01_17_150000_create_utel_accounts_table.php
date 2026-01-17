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
        Schema::create('utel_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name')->default('UTEL');
            $table->string('email'); // Login email
            $table->text('password'); // Encrypted password
            $table->text('access_token')->nullable(); // Bearer token
            $table->timestamp('token_expires_at')->nullable();
            $table->string('caller_id')->nullable(); // Default caller ID
            $table->string('extension')->nullable(); // SIP extension
            $table->boolean('is_active')->default(true);
            $table->integer('balance')->default(0); // Balance in UZS
            $table->string('currency')->default('UZS');
            $table->json('settings')->nullable();
            $table->string('webhook_secret')->nullable();
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
        Schema::dropIfExists('utel_accounts');
    }
};
