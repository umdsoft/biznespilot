<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('meta_account_id'); // act_123456789
            $table->string('name');
            $table->string('currency')->default('USD');
            $table->string('timezone')->nullable();
            $table->integer('account_status')->default(1); // 1=Active, 2=Disabled
            $table->decimal('amount_spent', 15, 2)->default(0);
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['integration_id', 'meta_account_id']);
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ad_accounts');
    }
};
