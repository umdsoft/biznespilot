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
        Schema::table('leads', function (Blueprint $table) {
            // Agar score ustuni yo'q bo'lsa qo'shish
            if (! Schema::hasColumn('leads', 'score')) {
                $table->unsignedTinyInteger('score')->default(0)->after('status');
            }

            // Scoring kategoriyasi (hot, warm, cool, cold, frozen)
            $table->string('score_category', 20)->nullable()->after('score');

            // Scoring tafsilotlari (qaysi qoidalar qo'llanilgani)
            $table->json('score_breakdown')->nullable()->after('score_category');

            // Oxirgi scoring vaqti
            $table->timestamp('scored_at')->nullable()->after('score_breakdown');

            // Score decay uchun
            $table->timestamp('last_engagement_at')->nullable()->after('scored_at');

            // Indexlar
            $table->index('score');
            $table->index('score_category');
            $table->index(['business_id', 'score_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'score_category']);
            $table->dropIndex(['score_category']);
            $table->dropIndex(['score']);

            $table->dropColumn([
                'score_category',
                'score_breakdown',
                'scored_at',
                'last_engagement_at',
            ]);
        });
    }
};
