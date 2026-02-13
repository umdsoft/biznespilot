<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dream_buyers jadvaliga semantik ustunlarni qo'shish.
     *
     * Mavjud q-ustunlar:
     *   q3_where_do_they_hang_out  → accessor: where_spend_time
     *   q5_what_are_they_afraid_of → accessor: fears
     *   q6_what_are_they_frustrated_with → accessor: frustrations
     *   q8_what_do_they_secretly_want   → accessor: dreams
     *
     * Yangi ustunlar (q-ekvivalenti yo'q):
     *   info_sources, language_style, communication_preferences,
     *   daily_routine, happiness_triggers
     */
    public function up(): void
    {
        Schema::table('dream_buyers', function (Blueprint $table) {
            if (! Schema::hasColumn('dream_buyers', 'info_sources')) {
                $table->text('info_sources')->nullable()->after('q9_how_do_they_make_decisions');
            }
            if (! Schema::hasColumn('dream_buyers', 'language_style')) {
                $table->text('language_style')->nullable()->after('info_sources');
            }
            if (! Schema::hasColumn('dream_buyers', 'communication_preferences')) {
                $table->text('communication_preferences')->nullable()->after('language_style');
            }
            if (! Schema::hasColumn('dream_buyers', 'daily_routine')) {
                $table->text('daily_routine')->nullable()->after('communication_preferences');
            }
            if (! Schema::hasColumn('dream_buyers', 'happiness_triggers')) {
                $table->text('happiness_triggers')->nullable()->after('daily_routine');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dream_buyers', function (Blueprint $table) {
            $columns = ['info_sources', 'language_style', 'communication_preferences', 'daily_routine', 'happiness_triggers'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('dream_buyers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
