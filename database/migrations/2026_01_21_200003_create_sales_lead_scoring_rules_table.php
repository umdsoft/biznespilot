<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lead scoring qoidalari jadvali
     */
    public function up(): void
    {
        Schema::create('sales_lead_scoring_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Qoida shartlari
            $table->string('field'); // phone, email, company, estimated_value, source.code, etc.
            $table->string('condition'); // not_null, equals, greater_than, less_than, contains
            $table->string('value')->nullable(); // Shart qiymati
            $table->string('value_type')->default('string'); // string, number, boolean

            // Ball
            $table->integer('points')->default(0); // Ijobiy yoki salbiy ball

            // Options
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->string('category')->default('general'); // completeness, value, engagement, source, negative

            $table->timestamps();

            $table->index(['business_id', 'is_active']);
            $table->index(['category']);
        });

        // Lead jadvaliga score maydoni qo'shish (agar mavjud bo'lmasa)
        if (! Schema::hasColumn('leads', 'score')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->unsignedTinyInteger('score')->default(50)->after('status');
                $table->string('score_category', 20)->default('warm')->after('score'); // hot, warm, cold, frozen
                $table->index('score');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_lead_scoring_rules');

        if (Schema::hasColumn('leads', 'score')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn(['score', 'score_category']);
            });
        }
    }
};
