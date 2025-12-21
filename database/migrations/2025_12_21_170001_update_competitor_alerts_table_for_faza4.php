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
        Schema::table('competitor_alerts', function (Blueprint $table) {
            // Add new columns for FAZA 4
            $table->text('description')->nullable()->after('message');
            $table->string('source_url')->nullable()->after('description');
            $table->json('detected_changes')->nullable()->after('source_url');
            $table->decimal('old_price', 15, 2)->nullable()->after('detected_changes');
            $table->decimal('new_price', 15, 2)->nullable()->after('old_price');
            $table->decimal('price_change_percent', 8, 2)->nullable()->after('new_price');
            $table->string('product_name')->nullable()->after('price_change_percent');
            $table->string('campaign_name')->nullable()->after('product_name');
            $table->text('action_recommendation')->nullable()->after('campaign_name');
            $table->boolean('requires_action')->default(false)->after('action_recommendation');
            $table->timestamp('action_taken_at')->nullable()->after('requires_action');
            $table->text('action_notes')->nullable()->after('action_taken_at');
            $table->boolean('is_important')->default(false)->after('action_notes');
            $table->boolean('is_active')->default(true)->after('is_important');

            // Rename status to new enum or add new status column
            $table->string('new_status')->default('new')->after('status');

            // Add index
            $table->index(['business_id', 'is_active', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitor_alerts', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'is_active', 'created_at']);

            $table->dropColumn([
                'description',
                'source_url',
                'detected_changes',
                'old_price',
                'new_price',
                'price_change_percent',
                'product_name',
                'campaign_name',
                'action_recommendation',
                'requires_action',
                'action_taken_at',
                'action_notes',
                'is_important',
                'is_active',
                'new_status',
            ]);
        });
    }
};
