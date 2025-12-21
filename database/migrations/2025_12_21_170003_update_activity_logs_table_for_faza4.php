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
        Schema::table('activity_logs', function (Blueprint $table) {
            // Add new columns for FAZA 4
            $table->string('action_category')->nullable()->after('action'); // auth, data, settings, report, integration, etc.
            $table->string('action_type')->nullable()->after('action_category'); // crud type: create, read, update, delete
            $table->string('entity_type')->nullable()->after('model_type'); // Alternative naming
            $table->string('entity_id')->nullable()->after('model_id'); // Alternative naming
            $table->string('entity_name')->nullable()->after('entity_id');
            $table->json('old_values')->nullable()->after('changes');
            $table->json('new_values')->nullable()->after('old_values');
            $table->string('session_id')->nullable()->after('user_agent');
            $table->string('device_type')->nullable()->after('session_id');
            $table->string('browser')->nullable()->after('device_type');
            $table->string('os')->nullable()->after('browser');
            $table->string('country')->nullable()->after('os');
            $table->string('city')->nullable()->after('country');
            $table->boolean('is_important')->default(false)->after('city');
            $table->boolean('is_system')->default(false)->after('is_important');

            // Add indexes
            $table->index(['business_id', 'action_category']);
            $table->index(['business_id', 'entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'action_category']);
            $table->dropIndex(['business_id', 'entity_type', 'entity_id']);

            $table->dropColumn([
                'action_category',
                'action_type',
                'entity_type',
                'entity_id',
                'entity_name',
                'old_values',
                'new_values',
                'session_id',
                'device_type',
                'browser',
                'os',
                'country',
                'city',
                'is_important',
                'is_system',
            ]);
        });
    }
};
