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
        Schema::table('content_generations', function (Blueprint $table) {
            $table->foreignUuid('offer_id')
                ->nullable()
                ->after('user_id')
                ->constrained('offers')
                ->nullOnDelete();

            $table->index(['business_id', 'offer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('content_generations', function (Blueprint $table) {
            $table->dropForeign(['offer_id']);
            $table->dropIndex(['content_generations_business_id_offer_id_index']);
            $table->dropColumn('offer_id');
        });
    }
};
