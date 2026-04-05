<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tezlik optimizatsiyasi uchun qo'shimcha indekslar.
 * Tez-tez ishlatiladigan so'rovlar uchun murakkab indekslar.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Agent xabarlar — kunlik hisob va suhbat tarix uchun
        if (!$this->indexExists('agent_messages', 'idx_business_created')) {
            Schema::table('agent_messages', function (Blueprint $table) {
                $table->index(['business_id', 'created_at'], 'idx_business_created');
                $table->index(['business_id', 'role', 'created_at'], 'idx_business_role_date');
            });
        }

        // AI sarfi — oylik hisob uchun
        if (!$this->indexExists('ai_usage_log', 'idx_agent_created')) {
            Schema::table('ai_usage_log', function (Blueprint $table) {
                $table->index(['agent_type', 'created_at'], 'idx_agent_created');
            });
        }

        // Sohaviy bilim — tez qidirish uchun
        if (!$this->indexExists('industry_objection_responses', 'idx_industry_type_success')) {
            Schema::table('industry_objection_responses', function (Blueprint $table) {
                $table->index(['industry', 'objection_type', 'success_rate'], 'idx_industry_type_success');
            });
        }
    }

    public function down(): void
    {
        Schema::table('agent_messages', function (Blueprint $table) {
            $table->dropIndex('idx_business_created');
            $table->dropIndex('idx_business_role_date');
        });
        Schema::table('ai_usage_log', function (Blueprint $table) {
            $table->dropIndex('idx_agent_created');
        });
        Schema::table('industry_objection_responses', function (Blueprint $table) {
            $table->dropIndex('idx_industry_type_success');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) return true;
        }
        return false;
    }
};
