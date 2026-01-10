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
        // Payment Accounts (Payme/Click credentials for each business)
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('provider'); // payme, click
            $table->string('name')->default('To\'lov tizimi');

            // Payme credentials
            $table->string('merchant_id')->nullable(); // Payme Merchant ID
            $table->string('merchant_key')->nullable(); // Payme Secret Key

            // Click credentials
            $table->string('service_id')->nullable(); // Click Service ID
            $table->string('merchant_user_id')->nullable(); // Click Merchant User ID
            $table->string('secret_key')->nullable(); // Click Secret Key

            $table->boolean('is_active')->default(true);
            $table->boolean('is_test_mode')->default(false); // Test/Production mode
            $table->json('settings')->nullable(); // Additional settings
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'provider']);
            $table->index(['business_id', 'is_active']);
        });

        // Payment Transactions
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('lead_id')->nullable();
            $table->uuid('payment_account_id');
            $table->uuid('created_by')->nullable(); // User who created the payment link

            $table->string('provider'); // payme, click
            $table->string('provider_transaction_id')->nullable(); // External transaction ID
            $table->string('order_id')->unique(); // Our internal order ID

            $table->decimal('amount', 15, 2); // Amount in UZS
            $table->string('currency', 3)->default('UZS');

            $table->enum('status', [
                'pending',      // Payment link created, waiting for payment
                'processing',   // Payment in progress
                'completed',    // Payment successful
                'cancelled',    // Payment cancelled
                'failed',       // Payment failed
                'refunded',     // Payment refunded
            ])->default('pending');

            $table->string('payment_url')->nullable(); // Payment link URL
            $table->string('return_url')->nullable(); // Return URL after payment

            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional data from provider

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('payment_account_id')->references('id')->on('payment_accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'created_at']);
            $table->index(['lead_id']);
            $table->index('provider_transaction_id');
            $table->index('order_id');
        });

        // Payme Transaction States (for tracking Payme specific states)
        Schema::create('payme_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_transaction_id');
            $table->string('payme_transaction_id')->nullable(); // Payme's transaction ID
            $table->bigInteger('payme_time')->nullable(); // Payme's timestamp
            $table->integer('state')->default(1); // Payme transaction state
            $table->integer('reason')->nullable(); // Cancel reason
            $table->bigInteger('create_time')->nullable();
            $table->bigInteger('perform_time')->nullable();
            $table->bigInteger('cancel_time')->nullable();
            $table->timestamps();

            $table->foreign('payment_transaction_id')->references('id')->on('payment_transactions')->onDelete('cascade');
            $table->index('payme_transaction_id');
        });

        // Click Transaction States
        Schema::create('click_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_transaction_id');
            $table->bigInteger('click_trans_id')->nullable(); // Click's transaction ID
            $table->bigInteger('click_paydoc_id')->nullable(); // Click's payment document ID
            $table->string('merchant_prepare_id')->nullable(); // Our prepare ID
            $table->integer('error_code')->default(0);
            $table->string('error_note')->nullable();
            $table->timestamps();

            $table->foreign('payment_transaction_id')->references('id')->on('payment_transactions')->onDelete('cascade');
            $table->index('click_trans_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('click_transactions');
        Schema::dropIfExists('payme_transactions');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_accounts');
    }
};
