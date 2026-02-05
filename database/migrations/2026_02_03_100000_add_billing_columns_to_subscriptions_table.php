<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('billing_cycle', 20)->default('monthly')->after('status');
            $table->boolean('auto_renew')->default(true)->after('currency');
            $table->string('payment_provider', 20)->nullable()->after('auto_renew');
            $table->timestamp('last_payment_at')->nullable()->after('payment_provider');
            $table->uuid('scheduled_plan_id')->nullable()->after('last_payment_at');
            $table->timestamp('scheduled_change_at')->nullable()->after('scheduled_plan_id');
            $table->timestamp('scheduled_cancellation_at')->nullable()->after('scheduled_change_at');

            $table->foreign('scheduled_plan_id')->references('id')->on('plans')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['scheduled_plan_id']);
            $table->dropColumn([
                'billing_cycle',
                'auto_renew',
                'payment_provider',
                'last_payment_at',
                'scheduled_plan_id',
                'scheduled_change_at',
                'scheduled_cancellation_at',
            ]);
        });
    }
};
