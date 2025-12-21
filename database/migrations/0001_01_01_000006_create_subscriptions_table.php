<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('plan_id');
            $table->string('status', 20)->default('active'); // active, trial, cancelled, expired
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('UZS');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('restrict');

            $table->index('business_id');
            $table->index('status');
            $table->index(['status', 'ends_at']);
            $table->softDeletes();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('subscription_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('UZS');
            $table->string('status', 20)->default('pending'); // pending, completed, failed, refunded
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_provider', 50)->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('set null');

            $table->index('business_id');
            $table->index('status');
            $table->index('transaction_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
    }
};
