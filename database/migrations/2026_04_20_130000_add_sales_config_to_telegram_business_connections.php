<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_business_connections', function (Blueprint $table) {
            // Sales config
            $table->uuid('sales_script_id')->nullable()->after('persona_prompt');
            $table->uuid('primary_offer_id')->nullable()->after('sales_script_id');
            $table->boolean('auto_create_lead')->default(true)->after('primary_offer_id');
            $table->string('lead_initial_stage')->default('new')->after('auto_create_lead');

            // Knowledge base (products/services/faq) - JSON for flexibility
            $table->json('knowledge_base')->nullable()->after('lead_initial_stage');
            // Shape: {
            //   products: [{name, price, description, url}],
            //   faq: [{q, a}],
            //   contact: {phone, address, hours},
            //   payment_methods: [...],
            //   delivery: {...}
            // }

            $table->foreign('sales_script_id')->references('id')->on('sales_scripts')->nullOnDelete();
            $table->foreign('primary_offer_id')->references('id')->on('offers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('telegram_business_connections', function (Blueprint $table) {
            $table->dropForeign(['sales_script_id']);
            $table->dropForeign(['primary_offer_id']);
            $table->dropColumn([
                'sales_script_id',
                'primary_offer_id',
                'auto_create_lead',
                'lead_initial_stage',
                'knowledge_base',
            ]);
        });
    }
};
