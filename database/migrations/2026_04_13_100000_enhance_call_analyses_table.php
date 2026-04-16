<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Call analyses jadvaliga yangi ustunlar:
 * - operator_id (kim gaplashdi)
 * - business_id (DRY, call_logs dan olinmasin)
 * - lead_id (qaysi lid uchun)
 * - outcome (sale/lead/lost — lid natijasi)
 * - sentiment_customer, sentiment_operator (kayfiyat)
 * - talk_ratio_operator (0-100, operator qancha vaqt gapirdi)
 * - script_compliance_score (0-100)
 * - script_id (qaysi skript asosida)
 * - predicted_outcome (AI bashorati: win/lost)
 * - emotional_moments (JSON — qachon jahl chiqdi)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_analyses', function (Blueprint $table) {
            // Bog'lanishlar
            $table->uuid('business_id')->nullable()->after('call_log_id')->index();
            $table->uuid('operator_id')->nullable()->after('business_id')->index();
            $table->uuid('lead_id')->nullable()->after('operator_id')->index();
            $table->uuid('script_id')->nullable()->after('lead_id')->index();

            // Natija va bashorat
            $table->enum('outcome', ['sale', 'lead', 'lost', 'unknown'])->default('unknown')->after('overall_score');
            $table->enum('predicted_outcome', ['win', 'lost', 'uncertain'])->default('uncertain')->after('outcome');
            $table->decimal('win_probability', 5, 2)->default(0)->after('predicted_outcome');

            // Sentiment
            $table->enum('sentiment_customer', ['positive', 'neutral', 'negative'])->default('neutral')->after('win_probability');
            $table->enum('sentiment_operator', ['positive', 'neutral', 'negative'])->default('neutral')->after('sentiment_customer');

            // Talk ratios
            $table->decimal('talk_ratio_operator', 5, 2)->default(0)->after('sentiment_operator')->comment('0-100, operator qancha vaqt gapirdi');
            $table->integer('operator_words')->default(0)->after('talk_ratio_operator');
            $table->integer('customer_words')->default(0)->after('operator_words');

            // Script compliance
            $table->decimal('script_compliance_score', 5, 2)->default(0)->after('customer_words');
            $table->json('required_phrases_detected')->nullable()->after('script_compliance_score');
            $table->json('forbidden_phrases_detected')->nullable()->after('required_phrases_detected');

            // Emotional moments (jahl chiqqan payt va h.k.)
            $table->json('emotional_moments')->nullable()->after('forbidden_phrases_detected');
        });
    }

    public function down(): void
    {
        Schema::table('call_analyses', function (Blueprint $table) {
            $table->dropColumn([
                'business_id', 'operator_id', 'lead_id', 'script_id',
                'outcome', 'predicted_outcome', 'win_probability',
                'sentiment_customer', 'sentiment_operator',
                'talk_ratio_operator', 'operator_words', 'customer_words',
                'script_compliance_score', 'required_phrases_detected',
                'forbidden_phrases_detected', 'emotional_moments',
            ]);
        });
    }
};
